<?php
namespace App\Http\Controllers;

use App\Contracts\OtpSender;
use App\Models\OtpRequest;
use App\Models\User;
use App\Services\EmailOtpSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AuthController extends Controller
{
    public function showLogin() { return view('auth.login'); }
    public function showRegister() { return view('auth.mobile', ['defaultCountryCode'=>config('otp.default_country_code')]); }
    public function showOtp(Request $request) {
        $channel=$request->session()->get('otp.channel','mobile');
        $destination=$request->session()->get('otp.destination',$request->session()->get('otp.mobile_e164'));
        abort_unless($destination, 403);
        $country=$request->session()->get('otp.country_code');
        return view('auth.otp', ['channel'=>$channel,'destination'=>$destination,'countryCode'=>$country,'nationalNumber'=>$channel==='mobile'?substr($destination,strlen((string)$country)):null,'resendSeconds'=>config('otp.resend_seconds')]);
    }
    public function login(Request $request) {
        $request->validate(['email'=>'required|email','password'=>'required']);
        if (!Auth::attempt($request->only('email','password'), $request->boolean('remember'))) throw ValidationException::withMessages(['email'=>'Invalid credentials.']);
        $request->session()->regenerate();
        if($request->session()->has('validated_group_invitation'))return redirect()->route('groups.join.complete');
        return redirect()->intended(route('dashboard'));
    }
    public function register(Request $request) { return $this->sendOtp($request, app(OtpSender::class), app(EmailOtpSender::class)); }

    public function sendOtp(Request $request, OtpSender $sender, EmailOtpSender $emailSender) {
        $request->merge(['channel'=>$request->input('channel','mobile')]);
        $data = $request->validate([
            'channel'=>['required','in:mobile,email'],
            'country_code'=>['required_if:channel,mobile','nullable','regex:/^\+[1-9]\d{0,3}$/'],
            'phone'=>['required_if:channel,mobile','nullable','string','max:20'],
            'email'=>['required_if:channel,email','nullable','email:rfc','max:255'],
        ]);
        $channel=$data['channel'];
        if ($channel==='mobile') {
            $digits=preg_replace('/\D+/', '', (string)$data['phone']);
            if (strlen($digits)<7 || strlen($digits)>15 || ($data['country_code']==='+91' && !preg_match('/^[6-9]\d{9}$/',$digits)))
                throw ValidationException::withMessages(['phone'=>'Enter a valid mobile number for the selected country.']);
            $destination=$data['country_code'].$digits;
        } else {
            $destination=strtolower(trim($data['email']));
        }
        $field=$channel==='mobile'?'mobile_e164':'email';
        $latest=OtpRequest::where('channel',$channel)->where($field,$destination)->latest()->first();
        if ($latest && !$latest->verified_at && $latest->resend_available_at->isFuture())
            throw ValidationException::withMessages([$field==='email'?'email':'phone'=>'Please wait '.(int)$latest->resend_available_at->diffInSeconds(now()).' seconds before requesting another code.']);
        $code = config('otp.test_mode') && app()->environment('local','testing') ? config('otp.test_code') : str_pad((string)random_int(0,999999),6,'0',STR_PAD_LEFT);
        $otp = OtpRequest::create([$field=>$destination,'channel'=>$channel,'otp_hash'=>Hash::make($code),'expires_at'=>now()->addMinutes(config('otp.expiry_minutes')),'resend_available_at'=>now()->addSeconds(config('otp.resend_seconds'))]);
        try {
            $channel==='email' ? $emailSender->send($destination,$code) : $sender->send($destination,$code);
        } catch (RuntimeException $e) {
            $otp->delete(); report($e);
            throw ValidationException::withMessages([$field==='email'?'email':'phone'=>'We could not send the code right now. Please try again later.']);
        }
        $request->session()->put(['otp.channel'=>$channel,'otp.destination'=>$destination]);
        if ($channel==='mobile') $request->session()->put(['otp.mobile_e164'=>$destination,'otp.country_code'=>$data['country_code']]);
        else $request->session()->put('otp.email',$destination);
        return $request->expectsJson() ? response()->json(['message'=>'OTP sent.']) : redirect()->route('otp.show');
    }

    public function verifyOtp(Request $request) {
        $data=$request->validate(['otp'=>['required','digits:6']]);
        $channel=$request->session()->get('otp.channel','mobile');
        $destination=$request->session()->get('otp.destination',$request->session()->get('otp.mobile_e164'));
        if (!$destination) throw ValidationException::withMessages(['otp'=>'Your verification session expired. Please start again.']);
        $field=$channel==='email'?'email':'mobile_e164';
        $otp=OtpRequest::where('channel',$channel)->where($field,$destination)->whereNull('verified_at')->latest()->first();
        if (!$otp || $otp->expires_at->isPast()) throw ValidationException::withMessages(['otp'=>'This code has expired. Request a new one.']);
        if ($otp->attempts >= config('otp.max_attempts')) throw ValidationException::withMessages(['otp'=>'Too many incorrect attempts. Request a new code.']);
        if (!Hash::check($data['otp'],$otp->otp_hash)) { $otp->increment('attempts'); throw ValidationException::withMessages(['otp'=>'The code is incorrect. Please try again.']); }
        $otp->update(['verified_at'=>now()]);
        if ($channel==='email') {
            $user=User::where('email',$destination)->first();
            if (!$user) $user=User::create(['name'=>'New user','email'=>$destination,'email_verified_at'=>now(),'password'=>Hash::make(Str::random(40)),'onboarding_step'=>'role_pending','plan'=>'free']);
            else $user->update(['email_verified_at'=>now()]);
        } else {
            $user=User::where('mobile_e164',$destination)->orWhere('phone',$destination)->first();
            if (!$user) $user=User::create(['name'=>'New user','phone'=>$destination,'mobile_e164'=>$destination,'mobile_country_code'=>$request->session()->get('otp.country_code'),'password'=>Hash::make(Str::random(40)),'phone_verified_at'=>now(),'onboarding_step'=>'role_pending','plan'=>'free']);
            else $user->update(['mobile_e164'=>$destination,'phone_verified_at'=>now()]);
        }
        Auth::login($user); $request->session()->regenerate(); $request->session()->forget('otp');
        $target=$request->session()->has('validated_group_invitation')
            ? route($user->onboarding_completed_at?'groups.join.complete':'onboarding.resume')
            : ($request->session()->has('pending_invitation')
            ? route('invitations.show',$request->session()->get('pending_invitation'))
            : route($user->onboarding_completed_at ? 'dashboard' : 'onboarding.resume'));
        return $request->expectsJson() ? response()->json(['redirect'=>$target]) : redirect()->to($target);
    }
    public function logout(Request $request) { Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect()->route('home'); }
}
