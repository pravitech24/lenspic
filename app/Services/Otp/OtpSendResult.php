<?php

namespace App\Services\Otp;

final readonly class OtpSendResult
{
    public function __construct(public bool $accepted, public ?string $failureCategory = null) {}
}
