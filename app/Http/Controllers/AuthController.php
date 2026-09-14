<?php
namespace App\Http\Controllers;

use App\Contracts\OtpSender;
use App\Models\OtpRequest;
use App\Models\User;
use App\Services\EmailOtpSender;
use App\Services\Otp\{OtpProviderResolver, SmsOtpChallenges};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Inertia\Inertia;
use App\Services\Auth\AuthenticatedLanding;

class AuthController extends Controller
{
    public function showLogin() { return Inertia::render('Auth/Login'); }
    public function showRegister() { return Inertia::render('Auth/Register', ['defaultCountryCode'=>config('otp.default_country_code'),'whatsappEnabled'=>config('otp.driver')==='whatsapp','requestReference'=>(string) Str::uuid(),'defaultChannel'=>app(OtpProviderResolver::class)->defaultChannel()]); }
    public function showOtp(Request $request) {
        $channel=$request->session()->get('otp.channel','mobile');
        $destination=$request->session()->get('otp.destination',$request->session()->get('otp.mobile_e164'));
        abort_unless($destination, 403);
        $country=$request->session()->get('otp.country_code');
        $challenge=OtpRequest::where('public_reference',$request->session()->get('otp.challenge'))->first();
        if ($channel==='sms') abort_unless($challenge && hash_equals((string)$challenge->session_hash, app(SmsOtpChallenges::class)->sessionHash($request)),403);
        $latest=OtpRequest::where('channel',$channel)->where($channel==='email'?'email':'mobile_e164',$destination)->latest('id')->first();
        $resendSeconds=$latest ? max(0,(int)ceil(now()->diffInSeconds($latest->resend_available_at,false))) : (int)config('otp.resend_seconds');
        $maskedDestination=$channel==='email' ? $destination : '******'.substr($destination,-4);
        return view('auth.otp', ['authenticationChannel'=>$challenge?->authentication_channel ?? ($channel==='mobile'?config('otp.driver'):$channel),'channel'=>$channel,'destination'=>$channel==='sms'?$maskedDestination:$destination,'maskedDestination'=>$maskedDestination,'countryCode'=>$country,'nationalNumber'=>$channel==='mobile'?substr($destination,strlen((string)$country)):null,'resendSeconds'=>$resendSeconds]);
    }
    public function login(Request $request) {
        $request->validate(['email'=>'required|email','password'=>'required']);
        if (!Auth::attempt($request->only('email','password'), $request->boolean('remember'))) throw ValidationException::withMessages(['email'=>'Invalid credentials.']);
        $request->session()->regenerate();
        return redirect()->to(app(AuthenticatedLanding::class)->afterAuthentication($request));
    }
    public function register(Request $request) { return $this->sendOtp($request, app(OtpSender::class), app(EmailOtpSender::class)); }

    public function sendOtp(Request $request, OtpSender $sender, EmailOtpSender $emailSender) {
        $request->validate(['resend'=>['sometimes','boolean']]);
        if ($request->boolean('resend')) {
            abort_unless($request->session()->has('otp.challenge'),403);
            $request->merge(['channel'=>$request->session()->get('otp.channel'),'phone'=>$request->session()->get('otp.destination'),'country_code'=>$request->session()->get('otp.country_code'),'email'=>$request->session()->get('otp.email')]);
        }
        $request->merge(['channel'=>$request->input('channel',app(OtpProviderResolver::class)->defaultChannel())]);
        $data = $request->validate([
            'request_id'=>['nullable','uuid'],
            'channel'=>['required',\Illuminate\Validation\Rule::in(OtpProviderResolver::CHANNELS)],
            'country_code'=>['required_if:channel,mobile,sms,whatsapp','nullable','regex:/^\+[1-9]\d{0,3}$/'],
            'phone'=>['required_if:channel,mobile,sms,whatsapp','nullable','string','max:32'],
            'email'=>['required_if:channel,email','nullable','email:rfc','max:255'],
        ]);
        $channel=$data['channel'];
        if ($channel==='mobile' && config('otp.driver')==='sms') $channel='sms';
        if ($channel!=='email') {
            try { $destination=app(\App\Services\WhatsAppLinks::class)->normalize($data['phone'], $data['country_code']); }
            catch (\InvalidArgumentException $e) { throw ValidationException::withMessages(['phone'=>$e->getMessage()]); }
        } else {
            $destination=strtolower(trim($data['email']));
        }
        if ($channel==='sms') {
            $otp=app(SmsOtpChallenges::class)->send($request,$destination,$data['request_id']??null);
            $parsed=\libphonenumber\PhoneNumberUtil::getInstance()->parse($destination,null);
            $request->session()->put(['otp.channel'=>'sms','otp.destination'=>$destination,'otp.mobile_e164'=>$destination,'otp.country_code'=>'+'.$parsed->getCountryCode(),'otp.challenge'=>$otp->public_reference]);
            return $request->expectsJson() ? response()->json(['message'=>'Verification code sent successfully.']) : redirect()->route('otp.show');
        }
        if ($channel==='whatsapp') {
            $sender=app(OtpProviderResolver::class)->resolve('whatsapp');
            $channel='mobile';
        }
        $field=$channel==='mobile'?'mobile_e164':'email';
        $destinationKey=hash_hmac('sha256', $channel.':'.$destination, config('app.key'));
        $limitKey='otp-request:'.$destinationKey;
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($limitKey, 5))
            throw ValidationException::withMessages([$channel==='email'?'email':'phone'=>'Too many requests. Please try again later.']);
        \Illuminate\Support\Facades\RateLimiter::hit($limitKey, 600);
        $code = config('otp.test_mode') && app()->environment('local','testing') ? config('otp.test_code') : str_pad((string)random_int(0,999999),6,'0',STR_PAD_LEFT);
        $requestKey=isset($data['request_id']) ? hash_hmac('sha256', $destinationKey.':'.$data['request_id'], config('app.key')) : null;
        $otp = \Illuminate\Support\Facades\DB::transaction(function () use ($requestKey, $destinationKey, $channel, $field, $destination, $code, $sender) {
            \Illuminate\Support\Facades\DB::table('otp_destination_locks')->insertOrIgnore(['key'=>$destinationKey]);
            \Illuminate\Support\Facades\DB::table('otp_destination_locks')->where('key',$destinationKey)->lockForUpdate()->first();
            if ($requestKey && ($existing=OtpRequest::where('request_key',$requestKey)->first())) {
                if ($existing->expires_at->isPast() || $existing->verified_at) throw ValidationException::withMessages([$channel==='email'?'email':'phone'=>'This request has expired. Request a new code.']);
                return $existing;
            }
            $latest=OtpRequest::where('channel',$channel)->where($field,$destination)->latest('id')->first();
            if ($latest && $latest->resend_available_at->isFuture())
                throw ValidationException::withMessages([$channel==='email'?'email':'phone'=>'Please wait before requesting another code.']);
            OtpRequest::where('channel',$channel)->where($field,$destination)->whereNull('verified_at')->update(['expires_at'=>now()]);
            $otp=OtpRequest::create([$field=>$destination,'channel'=>$channel,'request_key'=>$requestKey,'public_reference'=>(string) Str::uuid(),
                'authentication_channel'=>$channel==='mobile' && $sender instanceof \App\Services\WhatsAppOtpSender ? 'whatsapp' : $channel,
                'disclosure_version'=>$channel==='mobile' && $sender instanceof \App\Services\WhatsAppOtpSender ? config('otp.disclosure_version') : null,
                'otp_hash'=>Hash::make($code),'expires_at'=>now()->addMinutes(config('otp.expiry_minutes')),'resend_available_at'=>now()->addSeconds(config('otp.resend_seconds'))]);
            if ($channel==='mobile' && $sender instanceof \App\Services\WhatsAppOtpSender) $sender->reserve($destination,$otp->public_reference);
            return $otp;
        });
        if (!$otp->wasRecentlyCreated && $channel==='mobile' && $sender instanceof \App\Services\WhatsAppOtpSender) {
            $record=$sender->reserve($destination,$otp->public_reference);
            if (!$record->accepted_at) throw ValidationException::withMessages(['phone'=>'Verification code requested. Please wait for delivery confirmation before trying again.']);
        }
        try {
            if ($channel==='email' && $otp->wasRecentlyCreated) $emailSender->send($destination,$code);
            elseif ($channel==='mobile' && $otp->wasRecentlyCreated && $sender instanceof \App\Services\WhatsAppOtpSender) $sender->send($destination,$code,$otp->public_reference);
            elseif ($channel==='mobile' && $otp->wasRecentlyCreated) $sender->send($destination,$code);
        } catch (RuntimeException $e) {
            $otp->update(['expires_at'=>now()]);
            // Do not report provider exceptions: their context may contain credentials or OTPs.
            throw ValidationException::withMessages([$channel==='email'?'email':'phone'=>'We could not confirm delivery. Wait a minute, then request a new code.']);
        }
        $request->session()->put(['otp.channel'=>$channel,'otp.destination'=>$destination,'otp.challenge'=>$otp->public_reference]);
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
        if ($channel==='sms') {
            $otp=app(SmsOtpChallenges::class)->verify($request,$data['otp']);
            $destination=$otp->mobile_e164;
        } else {
            $otp=OtpRequest::where('channel',$channel)->where($field,$destination)->where('public_reference',$request->session()->get('otp.challenge'))->whereNull('verified_at')->latest()->first();
            if (!$otp || $otp->expires_at->isPast()) throw ValidationException::withMessages(['otp'=>'This code has expired. Request a new one.']);
            $verifyKey='otp-verify:'.hash('sha256', (string)$otp->public_reference.':'.$destination);
            if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($verifyKey, (int) config('otp.max_attempts'))) throw ValidationException::withMessages(['otp'=>'Too many attempts. Request a new code.']);
            \Illuminate\Support\Facades\RateLimiter::hit($verifyKey, 600);
            if ($otp->attempts >= config('otp.max_attempts')) throw ValidationException::withMessages(['otp'=>'Too many incorrect attempts. Request a new code.']);
            if (!OtpRequest::whereKey($otp->id)->whereNull('verified_at')->where('attempts','<',config('otp.max_attempts'))->increment('attempts')) throw ValidationException::withMessages(['otp'=>'Too many attempts. Request a new code.']);
            if (!Hash::check($data['otp'],$otp->otp_hash)) { throw ValidationException::withMessages(['otp'=>'The code is incorrect. Please try again.']); }
            if (!OtpRequest::whereKey($otp->id)->whereNull('verified_at')->where('expires_at','>',now())->update(['verified_at'=>now()])) throw ValidationException::withMessages(['otp'=>'This code is no longer available. Request a new one.']);
        }
        if ($channel==='email') {
            $user=User::where('email',$destination)->first();
            if (!$user) $user=User::create(['name'=>'New user','email'=>$destination,'email_verified_at'=>now(),'password'=>Hash::make(Str::random(40)),'onboarding_step'=>'role_pending','plan'=>'free']);
            else $user->update(['email_verified_at'=>now()]);
        } else {
            $user=User::where('mobile_e164',$destination)->orWhere('phone',$destination)->first();
            if (!$user) $user=User::create(['name'=>'New user','phone'=>$destination,'mobile_e164'=>$destination,'mobile_country_code'=>$request->session()->get('otp.country_code'),'password'=>Hash::make(Str::random(40)),'phone_verified_at'=>now(),'onboarding_step'=>'role_pending','plan'=>'free']);
            else $user->update(['mobile_e164'=>$destination,'phone_verified_at'=>now()]);
        }
        if ($channel==='mobile') \App\Models\WhatsAppMessage::where('idempotency_key',hash('sha256','authentication_otp:'.$otp->public_reference))->update(['user_id'=>$user->id]);
        Auth::login($user); $request->session()->regenerate(); $request->session()->forget('otp');
        $target=app(AuthenticatedLanding::class)->afterAuthentication($request);
        return $request->expectsJson() ? response()->json(['redirect'=>$target]) : redirect()->to($target);
    }
    public function logout(Request $request) { Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect()->route('home'); }
}
