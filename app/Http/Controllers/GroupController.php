<?php

namespace App\Http\Controllers;

use App\Mail\GroupInvitationMail;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\GroupAccessInvite;
use App\Models\GroupAccessAudit;
use App\Http\Requests\StoreGroupRequest;
use App\Http\Requests\UpdateGroupRequest;
use App\Services\Media\{GroupCoverIngestor, MediaAssetCleanup};

class GroupController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $myGroups     = $user->createdGroups()->withCount('photos', 'members')->latest()->get();
        $joinedGroups = $user->groups()->withCount('photos', 'members')->latest()->get();
        return view('groups.index', compact('myGroups', 'joinedGroups'));
    }

    public function create() { return app(LensPicUiController::class)->create(request()); }

    public function store(StoreGroupRequest $request, GroupCoverIngestor $coverIngestor)
    {
        $this->authorize('create', Group::class);

        $user = Auth::user();
        $studioOwner=app(\App\Services\Team\TeamAuthorization::class)->ownerFor($user)??$user;
        if (!$studioOwner->canCreateGroup()) {
            if ($studioOwner->isTrial()) {
                return back()->with('error', 'Trial accounts can upload and share photos, but creating groups is disabled.');
            }

            return back()->with('error', 'You have reached your plan limit for creating groups.');
        }

        $data = collect($request->validated())->except('cover_photo','access_options','submission_token')->all();
        $data['creator_id']               = $studioOwner->id;
        $data['allow_guest_upload']        = $request->boolean('allow_guest_upload');
        $data['face_recognition_enabled']  = $request->boolean('face_recognition_enabled');
        $data['watermark_enabled']         = $request->boolean('watermark_enabled');
        $data['watermark_text']            = $request->input('watermark_text');

        $create = function () use ($data,$request,$coverIngestor,$studioOwner) { return DB::transaction(function () use ($data,$request,$coverIngestor,$studioOwner) { $group=Group::create($data); $group->members()->attach($studioOwner->id, ['role'=>'admin','join_method'=>'creator','membership_status'=>'active','access_type'=>'full_access','approved_at'=>now(),'approved_by'=>$request->user()->id,'joined_at'=>now()]); foreach(array_unique($request->input('access_options')) as $type) GroupAccessInvite::makeFor($group,$type,$request->user()->id); if($request->hasFile('cover_photo'))$coverIngestor->ingest($request->file('cover_photo'),$group,$request->user()); return $group; }); };
        $token = $request->input('submission_token');
        if ($token) {
            $key = 'group-create:'.Auth::id().':'.$token;
            $group = Cache::lock($key.':lock', 10)->block(5, function () use ($key,$create) {
                if ($id = Cache::get($key)) return Group::findOrFail($id);
                $group = $create(); Cache::put($key, $group->id, now()->addMinutes(30)); return $group;
            });
        } else $group = $create();

        app(\App\Services\Notifications\NotificationService::class)->send($studioOwner, 'group:'.$group->id.':created', ['category'=>'groups','title'=>'Group created','message'=>$group->name.' is ready.','studio_id'=>$studioOwner->id,'actor_id'=>$request->user()->id,'subject_type'=>'group','subject_id'=>(string)$group->id,'action_route'=>'groups.show','action_parameters'=>['group'=>$group->id],'action_label'=>'View Group','severity'=>'success']);

        return redirect()->route('groups.show', $group)->with('success', 'Group created. Upload photos or invite participants when ready.');
    }

    public function show(Group $group)
    {
        return app(LensPicUiController::class)->show(request(), $group);
    }

    public function edit(Group $group)
    {
        return app(LensPicUiController::class)->edit(request(), $group);
    }

    public function settings(Group $group)
    {
        return app(LensPicUiController::class)->edit(request(), $group);
    }

    public function update(UpdateGroupRequest $request, Group $group, GroupCoverIngestor $coverIngestor, MediaAssetCleanup $cleanup)
    {
        $this->authorize('update', $group);
        $data = collect($request->validated())->except(['cover_photo', 'remove_cover_photo'])->all();

        foreach (['is_active','anyone_with_link_can_join','downloads_enabled','favourites_enabled','participants_can_edit_identity','allow_guest_upload', 'face_recognition_enabled', 'watermark_enabled'] as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->boolean($field);
            }
        }

        DB::transaction(function () use ($request, $group, $data, $coverIngestor, $cleanup) {
            $group->update($data);

            if ($request->boolean('remove_cover_photo') && !$request->hasFile('cover_photo')) {
                $group->load('coverMediaAsset.variants', 'pendingCoverMediaAsset.variants');
                $covers = collect([$group->coverMediaAsset, $group->pendingCoverMediaAsset])->filter()->unique('id');
                $group->update(['cover_media_asset_id' => null, 'pending_cover_media_asset_id' => null]);
                foreach ($covers as $cover) $cleanup->schedule($cover);
            } elseif ($request->hasFile('cover_photo')) {
                $coverIngestor->ingest($request->file('cover_photo'), $group, $request->user());
            }
        });

        return back()->with('success', 'Group settings updated successfully.');
    }

    public function destroy(Group $group, MediaAssetCleanup $cleanup, \App\Services\Storage\StorageUsage $usage, \App\Services\Storage\PlanEntitlements $plans)
    {
        \Illuminate\Support\Facades\Gate::authorize("delete", $group);
        $group->load('coverMediaAsset.variants','pendingCoverMediaAsset.variants');
        $covers=collect([$group->coverMediaAsset,$group->pendingCoverMediaAsset])->filter()->unique('id');
        $group->update(['cover_media_asset_id'=>null,'pending_cover_media_asset_id'=>null]);
        foreach($covers as$cover)$cleanup->schedule($cover);
        $owner=$group->creator;$hours=$plans->for($owner)['deleted_media_usage_release_hours'];
        foreach ($group->photos()->with('mediaAsset')->get() as $photo) {
            if($asset=$photo->mediaAsset){$asset->delete();$photo->delete();$usage->record($owner,\App\Models\MediaQuotaUsageEvent::PHOTO_DELETED,$asset->quota_units,'group-photo-deleted-'.$asset->uuid,$group->id,$asset->id,Auth::id());\App\Jobs\PurgeDeletedMediaAsset::dispatch($asset->id)->delay(now()->addHours($hours))->afterCommit();}
            else{$photo->delete();}
        }
        $group->delete();
        return redirect()->route('groups.index')->with('success', 'Group deleted.');
    }

    public function join(Group $group)
    {
        abort(403,'Use a valid participant access code or invitation link to join this group.');
    }

    public function leave(Group $group)
    {
        $user = Auth::user();
        if ($group->creator_id === $user->id) {
            return back()->with('error', 'Creator cannot leave. Delete the group instead.');
        }
        $group->members()->detach($user->id);
        return redirect()->route('groups.index')->with('success', 'Left the group.');
    }

    public function invite(Request $request, Group $group)
    {
        $this->checkAdmin($group);
        $request->validate(['emails' => 'required|string']);
        $emails = array_values(array_unique(array_filter(array_map('trim', explode(',', $request->emails)))));
        foreach ($emails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages(['emails' => "[$email] is not a valid email address."]);
            }
        }

        $invite = GroupAccessInvite::firstOrCreate(
            ['group_id' => $group->id, 'access_type' => GroupAccessInvite::FULL],
            ['access_code' => GroupAccessInvite::generateCode(), 'invitation_token' => Str::random(48), 'created_by' => Auth::id()]
        );
        $sent = 0;
        foreach ($emails as $email) {
            $u = User::where('email', $email)->first();
            if ($u && !$group->isMember($u)) {
                $group->members()->attach($u->id, ['role'=>'member','membership_status'=>'active','access_type'=>'full_access','join_method'=>'direct_invitation','approved_at'=>now(),'approved_by'=>Auth::id(),'joined_at'=>now()]);
            }
            try {
                Mail::to($email)->send(new GroupInvitationMail($group, $invite));
                $sent++;
            } catch (\Throwable $exception) {
                report($exception);
            }
        }
        if ($sent === 0) return back()->with('error', 'The invitation emails could not be sent. Check the SMTP configuration.');
        return back()->with('success', "$sent invitation email(s) sent.");
    }

    public function members(Group $group)
    {
        return app(LensPicUiController::class)->members(request(), $group);
    }

    public function removeMember(Group $group, User $user)
    {
        $this->checkAdmin($group);
        if ($user->id === $group->creator_id) return back()->with('error', 'Cannot remove creator.');
        $group->members()->detach($user->id);
        app(\App\Services\AuditLogger::class)->log("group.member_removed", $user, $group, [], [], ["member_id" => $user->id]);
        return back()->with('success', 'Member removed.');
    }

    public function regenerateToken(Group $group)
    {
        $this->checkAdmin($group);
        $group->regenerateToken();
        return back()->with('success', 'Share link regenerated!');
    }

    public function joinCodeForm() { return view('groups.join-code'); }
    public function joinByCode(Request $request) {
        $code=strtoupper(preg_replace('/[\s-]+/','',(string)$request->input('event_code'))); $request->merge(['event_code'=>$code]);
        $request->validate(['event_code'=>['required','size:6','regex:/^[A-HJ-NP-Z2-9]{6}$/']]);
        $invite=GroupAccessInvite::where('access_code',$code)->first();
        if(!$invite || !$invite->isUsable()) throw ValidationException::withMessages(['event_code'=>'This access code is invalid, expired, revoked, or has reached its usage limit.']);
        return redirect()->route('invitations.show',$invite->invitation_token);
    }
    public function joinUser(Group $group, User $user, string $method, ?GroupAccessInvite $invite=null): string {
        return DB::transaction(function() use($group,$user,$method,$invite) {
            $group=Group::query()->lockForUpdate()->findOrFail($group->id);
            if (!$group->is_active || $group->membership_status!=='open') throw ValidationException::withMessages(['group'=>'This group is no longer accepting members.']);
            if($invite){$invite=GroupAccessInvite::query()->lockForUpdate()->findOrFail($invite->id);if(!$invite->isUsable())throw ValidationException::withMessages(['group'=>'This invitation is no longer available.']);}
            $access=$invite?->access_type ?? GroupAccessInvite::FULL; $existing=$group->members()->where('user_id',$user->id)->first();
            if($existing && in_array($existing->pivot->membership_status,['blocked','removed','rejected'])) throw ValidationException::withMessages(['group'=>'Your previous membership cannot be reactivated with an invitation.']);
            if($existing) {
                if($existing->pivot->access_type===GroupAccessInvite::FULL || $access===GroupAccessInvite::PARTIAL) return 'existing';
                $group->members()->updateExistingPivot($user->id,['access_type'=>GroupAccessInvite::FULL,'access_invite_id'=>$invite->id,'join_method'=>$method,'access_upgraded_at'=>now()]);
                GroupAccessAudit::create(['group_id'=>$group->id,'access_invite_id'=>$invite->id,'user_id'=>$user->id,'action'=>'access_upgraded','metadata'=>['from'=>GroupAccessInvite::PARTIAL,'to'=>GroupAccessInvite::FULL],'created_at'=>now()]); $invite->increment('used_count'); return 'upgraded';
            }
            if ($group->membership_limit && $group->members()->wherePivot('membership_status','active')->count() >= $group->membership_limit) throw ValidationException::withMessages(['group'=>'This group has reached its membership limit.']);
            $group->members()->attach($user->id,['role'=>'member','join_method'=>$method,'membership_status'=>'active','access_type'=>$access,'access_invite_id'=>$invite?->id,'approved_at'=>now(),'joined_at'=>now()]); if($invite)$invite->increment('used_count'); return 'joined';
        });
    }
    public function regenerateCode(Group $group) { $this->checkAdmin($group); do{$code=(string)random_int(100000,999999);}while(Group::where('event_code',$code)->exists()); $group->update(['event_code'=>$code,'event_code_expires_at'=>null]); return back()->with('success','Event code regenerated.'); }
    public function regenerateInvitation(Group $group) { $this->checkAdmin($group); $group->update(['invitation_token'=>\Illuminate\Support\Str::random(48),'invitation_expires_at'=>null]); return back()->with('success','Invitation link regenerated.'); }
    public function revokeInvitation(Group $group) { $this->checkAdmin($group); $group->update(['invitation_token'=>null,'invitation_expires_at'=>now()]); return back()->with('success','Invitation link revoked.'); }

    private function inviteState(Group $group, ?GroupAccessInvite $invite): ?array
    {
        if (!$invite) {
            return null;
        }

        return [
            'code' => $invite->access_code,
            'link' => $invite->url,
            'regenerateUrl' => route('groups.access-invites.regenerate', [$group, $invite]),
        ];
    }

    private function checkAccess(Group $group)
    {
        \Illuminate\Support\Facades\Gate::authorize("view", $group);
    }

    private function checkAdmin(Group $group)
    {
        \Illuminate\Support\Facades\Gate::authorize("update", $group);
    }
}
