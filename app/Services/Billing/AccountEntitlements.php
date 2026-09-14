<?php
namespace App\Services\Billing;
use App\Models\{SubscriptionPlan,User};
class AccountEntitlements
{
 public function snapshot(User$user):array
 {
  $subscription=$user->subscriptions()->where('status','active')->where(fn($q)=>$q->whereNull('expires_at')->orWhere('expires_at','>',now()))->latest()->with('entitlementSnapshot')->first();
  if($subscription?->entitlementSnapshot){$s=$subscription->entitlementSnapshot;return['plan_code'=>$s->plan_code,'photo_limit'=>$s->photo_limit,'photo_reuse_limit'=>$s->photo_reuse_limit,'video_limit_mb'=>$s->video_limit_mb,'team_seat_limit'=>$s->team_seat_limit,'group_limit'=>$s->group_limit,'guest_limit'=>$s->guest_limit,'features'=>$s->features?:[],'addons'=>$this->activeAddons($user)];}
  $expiredManaged=!$subscription&&$user->subscriptions()->whereHas('entitlementSnapshot')->where(fn($q)=>$q->where('status','!=','active')->orWhere('expires_at','<=',now()))->exists();
  $code=$expiredManaged?'free':(['pro'=>'essential','business'=>'premium','enterprise'=>'premium'][$user->plan]??($user->plan?:'free'));
  $plan=SubscriptionPlan::where('code',$code)->first()?:SubscriptionPlan::where('code','free')->firstOrFail();$presented=app(PlanCatalog::class)->present($plan);
  return['plan_code'=>$plan->code,'photo_limit'=>$plan->photo_storage_limit,'photo_reuse_limit'=>$plan->photo_reuse_limit,'video_limit_mb'=>$plan->video_storage_mb,'team_seat_limit'=>$plan->team_seat_limit,'group_limit'=>$plan->group_limit,'guest_limit'=>$plan->guest_limit,'features'=>collect($presented['features'])->mapWithKeys(fn($f)=>[$f['code']=>true])->all(),'addons'=>$this->activeAddons($user)];
 }
 public function allows(User$user,string$feature):bool{$s=$this->snapshot($user);return(bool)($s['features'][$feature]??false)||in_array($feature,$s['addons'],true);}
 public function integerLimit(User$user,string$code):?int{$s=$this->snapshot($user);return match($code){'photo_storage'=>$s['photo_limit'],'photo_reuse'=>$s['photo_reuse_limit'],'video_storage'=>$s['video_limit_mb'],'team_seats'=>$s['team_seat_limit'],'group_limit'=>$s['group_limit'],'guest_limit'=>$s['guest_limit'],default=>null};}
 public function assertAllows(User$user,string$feature):void{abort_unless($this->allows($user,$feature),403,'Your current plan does not include this feature.');}
 private function activeAddons(User$user):array{return$user->subscriptionAddons()->where('status','active')->where('starts_at','<=',now())->where('expires_at','>',now())->with('feature:id,code')->get()->pluck('feature.code')->filter()->values()->all();}
}
