<?php
namespace App\Http\Controllers;
use App\Models\GroupAccessInvite;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InvitationController extends Controller {
    private function resolve(string $token): GroupAccessInvite {
        $invite=GroupAccessInvite::with(['group.creator'])->where('invitation_token',$token)->first();
        if(!$invite || !$invite->isUsable() || !$invite->group->is_active) abort(404,'This invitation is invalid, expired, revoked, or fully used.');
        return $invite;
    }
    public function show(Request $request,string $token) {
        $invite=$this->resolve($token); $group=$invite->group;
        if(!$request->user()){ $request->session()->put('pending_invitation',$token); return redirect()->route('register'); }
        if(!$request->user()->onboarding_completed_at){$request->session()->put('pending_invitation',$token);return redirect()->route('onboarding.resume');}
        $membership=$group->membershipFor($request->user()); return view('groups.invitation',compact('group','invite','membership'));
    }
    public function accept(Request $request,string $token) {
        $invite=$this->resolve($token); $result=app(GroupController::class)->joinUser($invite->group,$request->user(),'invitation_link',$invite);
        $request->session()->forget('pending_invitation'); $message=match($result){'upgraded'=>'Your membership was upgraded to Full Access.','existing'=>'You are already a member of this group.',default=>'You joined with '.$invite->label.'.'};
        $target=$invite->access_type===GroupAccessInvite::PARTIAL?'face.show':'groups.show'; return redirect()->route($target,$invite->group)->with('success',$message);
    }
}
