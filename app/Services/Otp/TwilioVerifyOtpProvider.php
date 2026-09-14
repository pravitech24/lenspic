<?php

namespace App\Services\Otp;

use App\Contracts\OtpProvider;
use RuntimeException;
use Throwable;
use Twilio\Exceptions\{EnvironmentException, RestException};
use Twilio\Rest\Client;

class TwilioVerifyOtpProvider implements OtpProvider
{
    public function validateConfiguration(): void
    {
        foreach (['account_sid'=>'AC', 'verify_service_sid'=>'VA'] as $key => $prefix) {
            if (!preg_match('/^'.$prefix.'[a-fA-F0-9]{32}$/', (string) config('services.twilio.'.$key))) {
                throw new RuntimeException('Twilio Verify configuration is missing or invalid: '.$key.'.');
            }
        }
        if (blank(config('services.twilio.auth_token')) || config('services.twilio.channel') !== 'sms') {
            throw new RuntimeException('Twilio Verify requires an auth token and the sms channel.');
        }
    }

    public function send(string $phoneNumber): OtpSendResult
    {
        try { $this->validateConfiguration(); }
        catch (RuntimeException) { return new OtpSendResult(false, 'configuration'); }
        try {
            $phoneNumber = app(PhoneNumberNormalizer::class)->normalize($phoneNumber);
            $verification = app(Client::class)->verify->v2->services(config('services.twilio.verify_service_sid'))
                ->verifications->create($phoneNumber, 'sms', ['locale'=>config('services.twilio.locale', 'en')]);
            return $verification->status === 'pending'
                ? new OtpSendResult(true)
                : new OtpSendResult(false, 'provider_rejected');
        } catch (Throwable $exception) {
            return new OtpSendResult(false, $this->category($exception));
        }
    }

    public function verify(string $phoneNumber, #[\SensitiveParameter] string $code): OtpVerificationResult
    {
        if (!preg_match('/^[0-9]{6}$/D', $code)) return new OtpVerificationResult(false, 'invalid_or_expired');
        try { $this->validateConfiguration(); }
        catch (RuntimeException) { return new OtpVerificationResult(false, 'configuration'); }
        try {
            $phoneNumber = app(PhoneNumberNormalizer::class)->normalize($phoneNumber);
            $check = app(Client::class)->verify->v2->services(config('services.twilio.verify_service_sid'))
                ->verificationChecks->create(['to'=>$phoneNumber, 'code'=>$code]);
            return new OtpVerificationResult($check->status === 'approved', $check->status === 'approved' ? null : 'invalid_or_expired');
        } catch (Throwable $exception) {
            return new OtpVerificationResult(false, $this->category($exception));
        }
    }

    private function category(Throwable $exception): string
    {
        // Allowlisted categories only: never persist/chain/report SDK exception text or payloads.
        if ($exception instanceof RestException) {
            return match (true) {
                $exception->getStatusCode() === 401, $exception->getCode() === 20003 => 'authentication',
                $exception->getCode() === 21608 => 'trial_recipient',
                in_array($exception->getCode(), [21408, 60605, 60223], true) => 'geographic_or_policy',
                $exception->getStatusCode() === 429, in_array($exception->getCode(), [60202, 60203, 60207, 60212, 60624, 60626], true) => 'rate_limited',
                in_array($exception->getCode(), [20005], true) => 'billing_or_account',
                $exception->getStatusCode() === 404 => 'invalid_or_expired',
                $exception->getStatusCode() >= 500 => 'provider_unavailable',
                default => 'provider_rejected',
            };
        }
        if ($exception instanceof EnvironmentException) return 'connection_uncertain';
        if ($exception instanceof \InvalidArgumentException) return 'invalid_phone';
        return 'provider_unavailable';
    }
}
