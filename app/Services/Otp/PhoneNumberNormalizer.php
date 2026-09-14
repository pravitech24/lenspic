<?php

namespace App\Services\Otp;

use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use InvalidArgumentException;

class PhoneNumberNormalizer
{
    public function normalize(string $phone, ?string $countryCode = null): string
    {
        if (!preg_match('/^\+?[0-9\s().-]+$/D', trim($phone))) throw new InvalidArgumentException('Enter a valid phone number.');
        try {
            $util = PhoneNumberUtil::getInstance();
            $input = str_starts_with(trim($phone), '+') ? trim($phone) : ($countryCode ? $countryCode.$phone : $phone);
            $number = $util->parse($input, config('otp.default_region', 'IN'));
            if (!$util->isValidNumber($number) || ($number->hasExtension())) throw new InvalidArgumentException();
            $regions = config('otp.supported_regions', []);
            if ($regions && !in_array($util->getRegionCodeForNumber($number), $regions, true)) throw new InvalidArgumentException();
            return $util->format($number, PhoneNumberFormat::E164);
        } catch (\Throwable) {
            throw new InvalidArgumentException('Enter a valid phone number for a supported country.');
        }
    }

}
