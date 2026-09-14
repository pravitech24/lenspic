<?php

namespace App\Services\Otp;

final readonly class OtpVerificationResult
{
    public function __construct(public bool $approved, public ?string $failureCategory = null) {}
}
