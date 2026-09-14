<?php

namespace App\Services;

use App\Contracts\OtpSender;
use RuntimeException;

class SmsOtpSender implements OtpSender
{
    public function send(string $mobileE164, string $code): void
    {
        throw new RuntimeException('Use the Twilio Verify SMS channel; locally generated SMS codes are not supported.');
    }
}
