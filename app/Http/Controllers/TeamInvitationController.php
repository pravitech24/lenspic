<?php
namespace App\Http\Controllers;
use App\Models\{StudioTeamMembership,TeamInvitation,User};
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
class TeamInvitationController extends Controller {
 private function valid(TeamInvitation$invitation,string$token):bool{return filled($invitation->token_hash)&&hash_equals($invitation->token_hash,hash('sha256',$token))&&$invitation->effectiveStatus()==='pending';}
 public function show(Request$r,TeamInvitation$invitation,string$token){$valid=$this->valid($invitation,$token);if($valid)$r->session()->put('pending_team_invitation',['uuid'=>$invitation->uuid,'token'=>$token]);return Inertia::render('TeamInvitations/Show',['status'=>$valid?'valid':$invitation->effectiveStatus(),'invitation'=>$valid?['studio'=>$invitation->owner->studio_name,'inviter'=>$invitation->inviter?->name,'email'=>$invitation->email,'role'=>$invitation->role,'expires_at'=>$invitation->expires_at]:null,'authenticated'=>(bool)$r->user(),'emailMatches'=>$r->user()?Str::lower($r->user()->email)===Str::lower($invitation->email):false]);}
 public function accept(Request$r,TeamInvitation$invitation,string$token,AuditLogger$audit){abort_unless($this->valid($invitation,$token),410);abort_unless($r->user(),401);abort_unless(Str::lower($r->user()->email)===Str::lower($invitation->email),403);DB::transaction(function()use($r,$invitation,$audit){$locked=TeamInvitation::whereKey($invitation->id)->lockForUpdate()->firstOrFail();abort_unless($locked->effectiveStatus()==='pending',410);StudioTeamMembership::updateOrCreate(['studio_owner_id'=>$locked->studio_owner_id,'user_id'=>$r->user()->id],['uuid'=>(string)Str::uuid(),'role'=>$locked->role,'status'=>'active','permissions'=>$locked->permissions,'invited_by'=>$locked->invited_by,'joined_at'=>now(),'last_active_at'=>now()]);$locked->update(['status'=>'accepted','accepted_at'=>now()]);$audit->log('team.invitation.accepted',$locked,null,[],[],['studio_owner_id'=>$locked->studio_owner_id,'user_id'=>$r->user()->id]);});$r->session()->forget('pending_team_invitation');return redirect()->to(app(\App\Services\Auth\AuthenticatedLanding::class)->url($r->user()))->with('success','Team invitation accepted.');}
 public function decline(Request$r,TeamInvitation$invitation,string$token,AuditLogger$audit){abort_unless($this->valid($invitation,$token),410);$invitation->update(['status'=>'revoked','revoked_at'=>now()]);$audit->log('team.invitation.declined',$invitation,null,[],[],['studio_owner_id'=>$invitation->studio_owner_id]);$r->session()->forget('pending_team_invitation');return redirect()->route('home');}
}
