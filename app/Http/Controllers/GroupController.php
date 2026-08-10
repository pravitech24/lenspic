<?php

namespace App\Http\Controllers;

use App\Mail\GroupInvitationMail;
use App\Models\Group;
use App\Models\User;
use App\Services\ImageOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\GroupAccessInvite;
use App\Models\GroupAccessAudit;

class GroupController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $myGroups     = $user->createdGroups()->withCount('photos', 'members')->latest()->get();
        $joinedGroups = $user->groups()->withCount('photos', 'members')->latest()->get();
        return view('groups.index', compact('myGroups', 'joinedGroups'));
    }

    public function create() { abort_unless(Auth::user()->account_type === 'photographer' || Auth::user()->isAdmin(), 403); return view('groups.create'); }

    public function store(Request $request, ImageOptimizationService $imageOptimizationService)
    {
        $request->validate([
            'name'       => 'required|string|max:100',
            'event_type' => 'required|string',
            'privacy'    => 'required|in:public,private,link_only',
            'cover_photo'=> 'nullable|image|max:51200',
            'membership_limit'=>'nullable|integer|min:2|max:100000',
            'location'=>'nullable|string|max:255',
            'access_options'=>['required','array','min:1'],
            'access_options.*'=>['required','in:partial_access,full_access'],
        ]);

        $user = Auth::user();
        if (!$user->canCreateGroup()) {
            if ($user->isTrial()) {
                return back()->with('error', 'Trial accounts can upload and share photos, but creating groups is disabled.');
            }

            return back()->with('error', 'You have reached your plan limit for creating groups.');
        }

        $data = $request->except('cover_photo','access_options');
        $data['creator_id']               = Auth::id();
        $data['allow_guest_upload']        = $request->boolean('allow_guest_upload');
        $data['face_recognition_enabled']  = $request->boolean('face_recognition_enabled');
        $data['watermark_enabled']         = $request->boolean('watermark_enabled');
        $data['watermark_text']            = $request->input('watermark_text');

        if ($request->hasFile('cover_photo')) {
            $optimized = $imageOptimizationService->optimizeAndStore($request->file('cover_photo'), 'covers');
            $data['cover_photo'] = $optimized['path'];
        }

        abort_unless($user->account_type === 'photographer' || $user->isAdmin(), 403);
        $group = DB::transaction(function () use ($data,$request) { $group=Group::create($data); $group->members()->attach(Auth::id(), ['role'=>'admin','join_method'=>'creator','membership_status'=>'active','access_type'=>'full_access','approved_at'=>now(),'approved_by'=>Auth::id(),'joined_at'=>now()]); foreach(array_unique($request->input('access_options')) as $type) GroupAccessInvite::makeFor($group,$type,Auth::id()); return $group; });

        return redirect()->route('groups.access-invites.index', $group)->with('success', 'Group created! Share an invitation to add participants.');
    }

    public function show(Group $group)
    {
        $this->checkAccess($group);
        $user = Auth::user();
        if (!$group->isAdmin($user) && !$group->hasFullAccess($user)) return redirect()->route('face.show',$group);
        $photos   = $group->photos()->with('uploader', 'likes')->latest()->paginate(30);
        $folders  = $group->folders()->with('photos')->ordered()->get();
        $members  = $group->members()->limit(8)->get();
        $isAdmin  = $group->isAdmin($user);
        $isMember = $group->isMember($user);

        if ($isAdmin) {
            foreach ([GroupAccessInvite::PARTIAL, GroupAccessInvite::FULL] as $accessType) {
                GroupAccessInvite::firstOrCreate(
                    ['group_id' => $group->id, 'access_type' => $accessType],
                    [
                        'access_code' => GroupAccessInvite::generateCode(),
                        'invitation_token' => \Illuminate\Support\Str::random(48),
                        'created_by' => $user->id,
                    ]
                );
            }
        }
        $accessInvites = $group->accessInvites()->get()->keyBy('access_type');
        $inviteState = [
            'viewer' => $this->inviteState($group, $accessInvites->get(GroupAccessInvite::PARTIAL)),
            'guest' => $this->inviteState($group, $accessInvites->get(GroupAccessInvite::FULL)),
        ];

        return view('groups.show', compact('group', 'photos', 'folders', 'members', 'isAdmin', 'isMember', 'accessInvites', 'inviteState'));
    }

    public function edit(Group $group)
    {
        $this->checkAdmin($group);
        return view('groups.edit', compact('group'));
    }

    public function settings(Group $group)
    {
        $this->checkAdmin($group);
        return view('groups.settings', compact('group'));
    }

    public function update(Request $request, Group $group, ImageOptimizationService $imageOptimizationService)
    {
        $this->checkAdmin($group);
        $data = $request->validate([
            'name'       => 'sometimes|required|string|max:100',
            'description'=> 'sometimes|nullable|string',
            'event_type' => 'sometimes|required|string',
            'event_date' => 'sometimes|nullable|date',
            'privacy'    => 'sometimes|required|in:public,private,link_only',
            'cover_photo'=> 'nullable|image|max:102400',
            'allow_guest_upload'       => 'sometimes|boolean',
            'face_recognition_enabled' => 'sometimes|boolean',
            'watermark_enabled'        => 'sometimes|boolean',
            'watermark_text'           => 'sometimes|nullable|string|max:255',
        ]);

        unset($data['cover_photo']);

        foreach (['allow_guest_upload', 'face_recognition_enabled', 'watermark_enabled'] as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->boolean($field);
            }
        }

        if ($request->hasFile('cover_photo')) {
            if ($group->cover_photo) Storage::disk('public')->delete($group->cover_photo);
            $optimized = $imageOptimizationService->optimizeAndStore($request->file('cover_photo'), 'covers');
            $data['cover_photo'] = $optimized['path'];
        }

        $group->update($data);
        return back()->with('success', 'Group settings updated!');
    }

    public function destroy(Group $group)
    {
        \Illuminate\Support\Facades\Gate::authorize("delete", $group);
        foreach ($group->photos as $photo) {
            Storage::disk('public')->delete(array_filter([$photo->path, $photo->thumbnail_path]));
        }
        if ($group->cover_photo) Storage::disk('public')->delete($group->cover_photo);
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
        $this->checkAccess($group);
        $members = $group->members()->withPivot('role', 'joined_at')->paginate(20);
        $isAdmin = $group->isAdmin(Auth::user());
        return view('groups.members', compact('group', 'members', 'isAdmin'));
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
