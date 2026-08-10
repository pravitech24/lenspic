<?php
namespace App\Services;
use App\Contracts\OtpSender;
use RuntimeException;
class LogOtpSender implements OtpSender {
    public function send(string $mobileE164, string $code): void {
        if (!app()->environment('local', 'testing') || !config('otp.test_mode')) throw new RuntimeException('No production SMS provider is configured.');
    }
}
