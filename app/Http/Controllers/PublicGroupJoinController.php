<?php
namespace App\Http\Controllers;

use App\Models\GroupAccessInvite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PublicGroupJoinController extends Controller
{
    public function show() { return view('groups.join-public'); }

    public function validateCode(Request $request)
    {
        $code=strtoupper(preg_replace('/[^A-Z0-9]+/i','',(string)$request->input('code'))); $request->merge(['code'=>$code]);
        $request->validate([
            'code' => ['required', 'size:6', 'regex:/^(?=.*[A-Z])(?=.*[0-9])[A-Z0-9]{6}$/'],
        ], [
            'code.size' => 'The invitation code must contain exactly 6 characters.',
            'code.regex' => 'The invitation code must contain both letters and numbers.',
        ]);
        $invite=GroupAccessInvite::with('group.creator')->where('access_code',$code)->first();
        if(!$invite){Log::warning('Invalid group code attempt',['ip'=>$request->ip(),'code_hash'=>hash('sha256',$code)]);return response()->json(['message'=>'This invitation code is invalid.'],404);}
        if($invite->expires_at?->isPast())return response()->json(['message'=>'This invitation code has expired.'],410);
        if(!$invite->isUsable()||!$invite->group->is_active)return response()->json(['message'=>'This invitation is no longer available.'],422);
        $request->session()->put('validated_group_invitation',$invite->invitation_token);
        return response()->json(['message'=>'Invitation found.','data'=>['group_reference'=>$invite->invitation_token,'group_name'=>$invite->group->name,'photographer_name'=>$invite->group->creator->studio_name,'cover_image'=>$invite->group->cover_photo_url,'event_date'=>$invite->group->event_date?->format('F Y'),'access_type'=>$invite->label,'authenticated'=>auth()->check()]]);
    }

    public function complete(Request $request) { abort_unless($request->session()->has('validated_group_invitation'),403);return view('groups.join-complete'); }

    public function join(Request $request)
    {
        $token=$request->session()->get('validated_group_invitation');
        if(!$token)throw ValidationException::withMessages(['code'=>'The validated invitation has expired. Enter the code again.']);
        $invite=GroupAccessInvite::with('group')->where('invitation_token',$token)->first();
        if(!$invite||!$invite->isUsable()||!$invite->group->is_active)throw ValidationException::withMessages(['code'=>'This invitation is no longer available.']);
        $result=app(GroupController::class)->joinUser($invite->group,$request->user(),'access_code',$invite);
        $request->session()->forget('validated_group_invitation');$request->session()->put('last_joined_group',$invite->group->id);
        return response()->json(['message'=>$result==='existing'?'You are already a member of this group.':($result==='upgraded'?'Your access was upgraded successfully.':'You have successfully joined the group.'),'redirect_url'=>route('groups.join.success')]);
    }

    public function success(Request $request) { $group=$request->user()->groups()->findOrFail($request->session()->get('last_joined_group'));return view('groups.join-success',compact('group')); }
}
