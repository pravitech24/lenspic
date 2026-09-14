<?php

namespace App\Services;

use InvalidArgumentException;

class WhatsAppLinks
{
    public function normalize(string $phone, ?string $countryCode = null): string
    {
        return app(\App\Services\Otp\PhoneNumberNormalizer::class)->normalize($phone, $countryCode);
    }

    public function contact(?string $phone, ?string $countryCode, string $message): ?string
    {
        if (blank($phone)) return null;
        try { return 'https://wa.me/'.ltrim($this->normalize($phone, $countryCode), '+').'?text='.rawurlencode($message); }
        catch (InvalidArgumentException) { return null; }
    }

    public function support(): ?string
    {
        return config('whatsapp.support_enabled') ? $this->contact(config('whatsapp.support_number'), config('whatsapp.support_country_code'), config('whatsapp.support_message')) : null;
    }
}
