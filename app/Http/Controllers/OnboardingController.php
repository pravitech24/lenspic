<?php
namespace App\Http\Controllers;

use App\Models\PhotographerProfile;
use App\Models\SelfieVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class OnboardingController extends Controller
{
    public function resume(Request $request) {
        $u=$request->user();
        if ($u->onboarding_completed_at) return redirect()->route('welcome.start');
        return match($u->onboarding_step) {
            'selfie_pending'=>redirect()->route('onboarding.selfie'),
            'user_profile_pending','photographer_profile_pending'=>redirect()->route('onboarding.profile'),
            default=>redirect()->route('onboarding.role'),
        };
    }
    public function role(Request $request) { return view('auth.signup-role',['selectedRole'=>$request->user()->account_type]); }
    public function storeRole(Request $request) {
        $data=$request->validate(['role'=>['required',Rule::in(['user','photographer'])]]);
        $step=$data['role']==='photographer'?'selfie_pending':'user_profile_pending';
        $request->user()->update(['account_type'=>$data['role'],'role'=>$data['role'],'role_assigned_at'=>now(),'onboarding_step'=>$step]);
        return redirect()->route('onboarding.resume');
    }
    public function selfie(Request $request) {
        abort_unless($request->user()->account_type==='photographer',403);
        return view('auth.selfie-verification');
    }
    public function storeSelfie(Request $request) {
        abort_unless($request->user()->account_type==='photographer',403);
        $request->validate(['selfie'=>['required','image','mimes:jpeg,jpg,png,webp','max:8192','dimensions:min_width=320,min_height=320']]);
        $size=getimagesize($request->file('selfie')->getRealPath());
        if (!$size) return back()->withErrors(['selfie'=>'The captured image could not be read. Please retake it.']);
        $path=$request->file('selfie')->store('selfie-verifications/'.$request->user()->id, 'local');
        SelfieVerification::create(['user_id'=>$request->user()->id,'image_path'=>$path,'verification_status'=>'manual_review']);
        $request->user()->update(['onboarding_step'=>'photographer_profile_pending']);
        return redirect()->route('onboarding.profile')->with('success','Selfie captured and queued for review.');
    }
    public function profile(Request $request) {
        abort_if(!in_array($request->user()->onboarding_step,['user_profile_pending','photographer_profile_pending']),403);
        return view('auth.onboarding-profile',['user'=>$request->user()]);
    }
    public function storeProfile(Request $request) {
        $u=$request->user();
        if ($u->email_verified_at) $request->merge(['email'=>$u->email]);
        if ($u->account_type==='photographer') {
            $selfie=$u->selfieVerifications()->whereIn('verification_status',['passed','manual_review'])->latest()->first();
            if (!$selfie) throw \Illuminate\Validation\ValidationException::withMessages(['selfie'=>'A valid selfie quality check is required.']);
            $data=$request->validate(['first_name'=>['required','string','min:2','max:100'],'last_name'=>['required','string','min:2','max:100'],'company_name'=>['required','string','min:2','max:160'],'email'=>['required','email','max:255',Rule::unique('users','email')->ignore($u->id),Rule::unique('photographer_profiles','company_email')->ignore($u->photographerProfile?->id)]]);
            DB::transaction(function() use($u,$data,$selfie) { $email=strtolower(trim($data['email'])); $u->update(['name'=>trim($data['first_name']).' '.trim($data['last_name']),'email'=>$email,'onboarding_step'=>'completed','onboarding_completed_at'=>now()]); PhotographerProfile::updateOrCreate(['user_id'=>$u->id],['first_name'=>trim($data['first_name']),'last_name'=>trim($data['last_name']),'company_name'=>trim($data['company_name']),'company_email'=>$email,'selfie_verification_id'=>$selfie->id]); });
        } else {
            $data=$request->validate(['name'=>['required','string','min:2','max:100','regex:/^[\pL\pM .\'\-]+$/u'],'email'=>[$u->email_verified_at?'required':'nullable','email','max:255',Rule::unique('users','email')->ignore($u->id)]]);
            DB::transaction(fn()=>$u->update(['name'=>preg_replace('/\s+/',' ',trim($data['name'])),'email'=>isset($data['email'])?strtolower(trim($data['email'])):null,'onboarding_step'=>'completed','onboarding_completed_at'=>now()]));
        }
        if ($request->session()->has('validated_group_invitation')) return redirect()->route('groups.join.complete');
        if ($request->session()->has('pending_invitation')) return redirect()->route('invitations.show',$request->session()->get('pending_invitation'));
        return redirect()->route('welcome.start');
    }
    public function welcome(Request $request) { abort_unless($request->user()->onboarding_completed_at,403); return view('auth.welcome-start',['user'=>$request->user()]); }
}
