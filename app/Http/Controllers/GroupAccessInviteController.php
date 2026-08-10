<?php
namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupAccessAudit;
use App\Models\GroupAccessInvite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GroupAccessInviteController extends Controller {
    private function authorizeOwner(Group $group): void { abort_unless($group->isAdmin(auth()->user()),403); }
    private function child(Group $group,GroupAccessInvite $invite): void { abort_unless($invite->group_id===$group->id,404); }
    public function index(Group $group) { $this->authorizeOwner($group); $invites=$group->accessInvites()->orderBy('access_type')->get(); return view('groups.access-invites',compact('group','invites')); }
    public function store(Request $request,Group $group) { $this->authorizeOwner($group); $data=$request->validate(['access_type'=>['required',Rule::in([GroupAccessInvite::PARTIAL,GroupAccessInvite::FULL])]]); $invite=GroupAccessInvite::firstOrCreate(['group_id'=>$group->id,'access_type'=>$data['access_type']],['access_code'=>GroupAccessInvite::generateCode(),'invitation_token'=>Str::random(48),'created_by'=>auth()->id()]); return back()->with('success',$invite->label.' invitation enabled.'); }
    public function update(Request $request,Group $group,GroupAccessInvite $invite) { $this->authorizeOwner($group);$this->child($group,$invite);$data=$request->validate(['expires_at'=>'nullable|date|after:now','max_uses'=>'nullable|integer|min:1|max:1000000']);$invite->update($data);return back()->with('success','Invitation limits updated.'); }
    public function regenerate(Request $request,Group $group,GroupAccessInvite $invite) { $this->authorizeOwner($group);$this->child($group,$invite);$what=$request->validate(['regenerate'=>['required',Rule::in(['code','link','both'])]])['regenerate'];$old=['code'=>$invite->access_code];$invite->update(['access_code'=>in_array($what,['code','both'])?GroupAccessInvite::generateCode():$invite->access_code,'invitation_token'=>in_array($what,['link','both'])?Str::random(48):$invite->invitation_token,'is_active'=>true,'revoked_at'=>null]);$this->audit($group,$invite,'regenerated',['parts'=>$what,'previous_code'=>$old['code']]);if($request->expectsJson())return response()->json(['message'=>$invite->label.' invitation regenerated.','code'=>$invite->access_code,'url'=>$invite->url]);return back()->with('success',$invite->label.' invitation regenerated.'); }
    public function revoke(Group $group,GroupAccessInvite $invite) { $this->authorizeOwner($group);$this->child($group,$invite);$invite->update(['is_active'=>false,'revoked_at'=>now()]);$this->audit($group,$invite,'revoked');return back()->with('success','Invitation revoked. Existing members keep their access.'); }
    public function reactivate(Group $group,GroupAccessInvite $invite) { $this->authorizeOwner($group);$this->child($group,$invite);$invite->update(['is_active'=>true,'revoked_at'=>null]);$this->audit($group,$invite,'reactivated');return back()->with('success','Invitation reactivated.'); }
    public function print(Group $group,GroupAccessInvite $invite) { $this->authorizeOwner($group);$this->child($group,$invite);return view('groups.access-invite-print',compact('group','invite')); }
    private function audit(Group $g,GroupAccessInvite $i,string $action,array $meta=[]): void { GroupAccessAudit::create(['group_id'=>$g->id,'access_invite_id'=>$i->id,'user_id'=>auth()->id(),'action'=>$action,'metadata'=>$meta,'created_at'=>now()]); }
}
