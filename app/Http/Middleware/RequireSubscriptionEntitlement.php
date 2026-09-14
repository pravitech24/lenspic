<?php
namespace App\Http\Middleware;
use App\Models\Group;
use App\Services\Billing\AccountEntitlements;
use App\Services\Team\TeamAuthorization;
use Closure;
use Illuminate\Http\Request;
class RequireSubscriptionEntitlement{public function handle(Request$request,Closure$next,string$feature){$actor=$request->user();abort_unless($actor,401);$group=$request->route('group')??$request->route('g');if($feature==='find_my_photos'&&$request->isMethod('post')&&$group instanceof Group&&!\App\Models\BiometricConsent::where('uuid',$request->input('consent_uuid'))->where('group_id',$group->id)->where('user_id',$actor->id)->where('state','granted')->exists())return$next($request);$owner=$group instanceof Group?$group->creator:(app(TeamAuthorization::class)->ownerFor($actor)??$actor);app(AccountEntitlements::class)->assertAllows($owner,$feature);return$next($request);}}
