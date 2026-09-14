<?php
namespace App\Http\Controllers;

use App\Services\Auth\{OnboardingState, AuthenticatedLanding};
use App\Models\PhotographerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    public function resume(Request $request) {
        $step=app(OnboardingState::class)->step($request->user());
        return $step ? redirect()->route('onboarding.'.$step) : redirect()->to(app(AuthenticatedLanding::class)->afterAuthentication($request));
    }
    private function guardStep(Request $request, string $expected) {
        $step=app(OnboardingState::class)->step($request->user());
        if ($step!==$expected) return $step ? redirect()->route('onboarding.'.$step) : redirect()->to(app(AuthenticatedLanding::class)->afterAuthentication($request));
        return null;
    }
    public function role(Request $request) {
        if ($redirect=$this->guardStep($request,'role')) return $redirect;
        return Inertia::render('Onboarding',['step'=>'role','user'=>$request->user(),'accountType'=>null]);
    }
    public function storeRole(Request $request) {
        return DB::transaction(function () use ($request) {
            $user=\App\Models\User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $request->setUserResolver(fn()=>$user);
            if ($redirect=$this->guardStep($request,'role')) return $redirect;
            $data=$request->validate(['role'=>['required',Rule::in(['user','photographer'])]]);
            $step=$data['role']==='photographer'?'photographer_profile_pending':'user_profile_pending';
            $user->update(['account_type'=>$data['role'],'role_assigned_at'=>now(),'onboarding_step'=>$step]);
            \Illuminate\Support\Facades\Auth::setUser($user);
            return redirect()->route('onboarding.resume');
        });
    }
    // Retired onboarding capture URLs remain safe for old bookmarks and forms.
    public function selfie(Request $request) {
        return $this->resume($request);
    }
    public function storeSelfie(Request $request) {
        return $this->resume($request);
    }
    public function profile(Request $request) {
        if ($redirect=$this->guardStep($request,'profile')) return $redirect;
        return Inertia::render('Onboarding',['step'=>'profile','user'=>$request->user(),'accountType'=>app(OnboardingState::class)->photographer($request->user())?'photographer':'user','profileDefaults'=>array_merge(['name'=>$request->user()->name==='New user'?'':$request->user()->name,'email'=>$request->user()->email],$request->user()->photographerProfile?->only(['first_name','last_name','company_name'])??[])]);
    }
    public function storeProfile(Request $request) {
        if ($redirect=$this->guardStep($request,'profile')) return $redirect;
        $u=$request->user();
        if ($u->email_verified_at) $request->merge(['email'=>$u->email]);
        if (app(OnboardingState::class)->photographer($u)) {
            $data=$request->validate(['first_name'=>['required','string','min:2','max:100'],'last_name'=>['required','string','min:2','max:100'],'company_name'=>['required','string','min:2','max:160'],'email'=>['required','email','max:255',Rule::unique('users','email')->ignore($u->id),Rule::unique('photographer_profiles','company_email')->ignore($u->photographerProfile?->id)]]);
            DB::transaction(function() use($u,$data) { $email=strtolower(trim($data['email'])); $u->update(['name'=>trim($data['first_name']).' '.trim($data['last_name']),'email'=>$email,'onboarding_step'=>'completed','onboarding_completed_at'=>now()]); PhotographerProfile::updateOrCreate(['user_id'=>$u->id],['first_name'=>trim($data['first_name']),'last_name'=>trim($data['last_name']),'company_name'=>trim($data['company_name']),'company_email'=>$email]); });
        } else {
            $data=$request->validate(['name'=>['required','string','min:2','max:100','regex:/^[\pL\pM .\'\-]+$/u'],'email'=>[$u->email_verified_at?'required':'nullable','email','max:255',Rule::unique('users','email')->ignore($u->id)]]);
            DB::transaction(fn()=>$u->update(['name'=>preg_replace('/\s+/',' ',trim($data['name'])),'email'=>isset($data['email'])?strtolower(trim($data['email'])):null,'onboarding_step'=>'completed','onboarding_completed_at'=>now()]));
        }
        $u->unsetRelation('photographerProfile');
        if (!app(OnboardingState::class)->photographer($u) && !$request->session()->has('validated_group_invitation') && !$request->session()->has('pending_invitation') && !$request->session()->has('pending_team_invitation') && !$request->session()->has('find_my_photos_intent')) return redirect()->route('welcome.start');
        return redirect()->to(app(AuthenticatedLanding::class)->afterAuthentication($request));
    }
    public function welcome(Request $request) {
        $state=app(OnboardingState::class);
        if (!$state->complete($request->user())) return $this->resume($request);
        if ($state->photographer($request->user()) || $request->user()->isSuperAdmin()) return redirect()->to(app(AuthenticatedLanding::class)->afterAuthentication($request));
        return view('auth.welcome-start',['user'=>$request->user()]);
    }
}
