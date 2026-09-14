<?php

namespace App\Services\Otp;

use App\Models\OtpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log, RateLimiter};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SmsOtpChallenges
{
    public const UNAVAILABLE = 'SMS verification is temporarily unavailable. Please use another verification method.';
    public const INVALID = 'The verification code is invalid or has expired.';

    public function sessionHash(Request $request): string
    {
        if (!$request->session()->has('otp.browser_key')) $request->session()->put('otp.browser_key', Str::random(64));
        return hash_hmac('sha256', $request->session()->get('otp.browser_key'), config('app.key'));
    }

    public function send(Request $request, string $phone, ?string $requestId): OtpRequest
    {
        $phone=app(PhoneNumberNormalizer::class)->normalize($phone);
        $sessionHash = $this->sessionHash($request);
        $phoneHash = hash_hmac('sha256', $phone, config('app.key'));
        foreach (['ip'=>hash('sha256', (string)$request->ip()), 'session'=>$sessionHash] as $scope=>$key) {
            $this->limit('sms-hour:'.$scope.':'.$key, $scope === 'ip' ? 20 : 10);
        }
        $otp = DB::transaction(function () use ($phone, $phoneHash, $sessionHash, $requestId) {
            DB::table('otp_destination_locks')->insertOrIgnore(['key'=>$phoneHash]);
            DB::table('otp_destination_locks')->where('key', $phoneHash)->lockForUpdate()->first();
            $requestKey = $requestId ? hash_hmac('sha256', 'sms:'.$sessionHash.':'.$phoneHash.':'.$requestId, config('app.key')) : null;
            if ($requestKey && ($existing = OtpRequest::where('request_key', $requestKey)->first())) {
                if ($existing->delivery_status === 'accepted' && !$existing->verified_at && $existing->expires_at->isFuture()) return $existing;
                throw ValidationException::withMessages(['phone'=>self::UNAVAILABLE]);
            }
            $latest = OtpRequest::where('channel', 'sms')->where('mobile_e164', $phone)->latest('id')->first();
            if ($latest && $latest->resend_available_at->isFuture()) {
                throw ValidationException::withMessages(['phone'=>'Please wait before requesting another code.']);
            }
            $this->limit('sms-hour:phone:'.$phoneHash, (int)config('services.twilio.hourly_send_limit', 5));
            OtpRequest::where('channel', 'sms')->where('mobile_e164', $phone)->whereNull('verified_at')->update(['expires_at'=>now()]);
            return OtpRequest::create([
                'public_reference'=>(string)Str::uuid(), 'request_key'=>$requestKey,
                'channel'=>'sms', 'authentication_channel'=>'sms', 'provider'=>'twilio_verify',
                'mobile_e164'=>$phone, 'destination_masked'=>'******'.substr($phone, -4),
                'session_hash'=>$sessionHash, 'otp_hash'=>null, 'delivery_status'=>'pending',
                'expires_at'=>now()->addMinutes(max(1, min(10, (int)config('services.twilio.expiry_minutes', 10)))),
                'resend_available_at'=>now()->addSeconds(max(60, (int)config('services.twilio.resend_seconds', 60))),
            ]);
        });
        if (!$otp->wasRecentlyCreated) return $otp;
        // The durable pending record/cooldown precedes I/O. A duplicate cannot claim another POST.
        $result = app(OtpProviderResolver::class)->resolve('sms')->send($phone);
        $otp->update([
            'delivery_status'=>$result->accepted ? 'accepted' : 'failed',
            'accepted_at'=>$result->accepted ? now() : null,
            'failure_category'=>$result->failureCategory,
        ]);
        $this->audit($otp, 'send', $result->failureCategory);
        if (!$result->accepted) {
            $otp->update(['expires_at'=>now()]);
            throw ValidationException::withMessages(['phone'=>self::UNAVAILABLE]);
        }
        return $otp;
    }

    public function verify(Request $request, #[\SensitiveParameter] string $code): OtpRequest
    {
        $otp = OtpRequest::where('public_reference', $request->session()->get('otp.challenge'))
            ->where('session_hash', $this->sessionHash($request))->where('channel', 'sms')->first();
        if (!$otp || $otp->verified_at || $otp->expires_at->isPast() || $otp->delivery_status !== 'accepted') {
            throw ValidationException::withMessages(['otp'=>self::INVALID]);
        }
        $max = max(1, min(5, (int)config('services.twilio.max_attempts', 5)));
        $claimed = OtpRequest::whereKey($otp->id)->whereNull('verified_at')->where('delivery_status', 'accepted')
            ->where('expires_at', '>', now())->where('attempts', '<', $max)
            ->update(['delivery_status'=>'checking', 'attempts'=>DB::raw('attempts + 1')]);
        if (!$claimed) throw ValidationException::withMessages(['otp'=>self::INVALID]);
        $otp->refresh();
        $result = app(OtpProviderResolver::class)->resolve('sms')->verify($otp->mobile_e164, $code);
        $this->audit($otp, 'verify', $result->failureCategory);
        if (!$result->approved) {
            $otp->update(['delivery_status'=>'accepted', 'failure_category'=>$result->failureCategory]);
            $error=in_array($result->failureCategory,['invalid_or_expired','rate_limited'],true) ? self::INVALID : self::UNAVAILABLE;
            throw ValidationException::withMessages(['otp'=>$error]);
        }
        $consumed = OtpRequest::whereKey($otp->id)->whereNull('verified_at')->where('delivery_status', 'checking')
            ->where('expires_at', '>', now())->update(['verified_at'=>now(), 'delivery_status'=>'approved', 'failure_category'=>null]);
        if (!$consumed) throw ValidationException::withMessages(['otp'=>self::INVALID]);
        return $otp->fresh();
    }

    private function limit(string $key, int $maximum): void
    {
        if (RateLimiter::tooManyAttempts($key, $maximum)) {
            throw ValidationException::withMessages(['phone'=>'Too many verification requests. Please try again later.']);
        }
        RateLimiter::hit($key, 3600);
    }

    private function audit(OtpRequest $otp, string $operation, ?string $failure): void
    {
        Log::info('SMS verification '.$operation, [
            'correlation_id'=>$otp->public_reference, 'provider'=>'twilio_verify',
            'destination'=>$otp->destination_masked, 'status'=>$failure ? 'failed' : ($operation === 'send' ? 'accepted' : 'approved'),
            'failure_category'=>$failure,
        ]);
    }
}
