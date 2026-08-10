<?php

namespace App\Services;

use App\Contracts\OtpSender;
use RuntimeException;

class SmsOtpSender implements OtpSender
{
    public function send(string $mobileE164, string $code): void
    {
        throw new RuntimeException('SMS delivery is reserved for future configuration. Set OTP_DRIVER=whatsapp for now.');
    }
}
