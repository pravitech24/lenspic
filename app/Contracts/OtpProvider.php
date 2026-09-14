<?php

namespace App\Contracts;

use App\Services\Otp\{OtpSendResult, OtpVerificationResult};

interface OtpProvider
{
    public function send(string $phoneNumber): OtpSendResult;
    public function verify(string $phoneNumber, #[\SensitiveParameter] string $code): OtpVerificationResult;
}
