<?php
namespace App\Services\Team;
use App\Models\{TeamInvitation,User};
use App\Services\Billing\AccountEntitlements;
class TeamSeatUsage {
 public function summary(User$owner):array{$limit=app(AccountEntitlements::class)->integerLimit($owner,'team_seats');$members=$owner->studioTeamMembers()->whereIn('status',['active','suspended'])->count();$pending=$owner->teamInvitations()->where('status','pending')->where('expires_at','>',now())->count();$used=$members+$pending;return['used'=>$used,'limit'=>$limit,'remaining'=>$limit===null?null:max(0,$limit-$used),'reached'=>$limit!==null&&$used>=$limit,'unlimited'=>$limit===null];}
}
