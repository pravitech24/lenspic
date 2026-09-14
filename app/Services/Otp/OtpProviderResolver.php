<?php

namespace App\Services\Otp;

use App\Contracts\{OtpProvider, OtpSender};
use App\Services\{EmailOtpSender, WhatsAppOtpSender};
use InvalidArgumentException;

class OtpProviderResolver
{
    public const CHANNELS = ['sms', 'whatsapp', 'email', 'mobile'];

    public function resolve(string $channel): OtpProvider|OtpSender|EmailOtpSender
    {
        return match ($channel) {
            'sms' => app(TwilioVerifyOtpProvider::class),
            'whatsapp' => app(WhatsAppOtpSender::class),
            'email' => app(EmailOtpSender::class),
            'mobile' => app(OtpSender::class), // Compatibility with existing mobile clients/test mode.
            default => throw new InvalidArgumentException('Unsupported verification method.'),
        };
    }

    public function defaultChannel(): string
    {
        $channel = config('otp.default_channel');
        if ($channel==='mobile' && config('otp.driver')==='sms') return 'sms';
        return in_array($channel, self::CHANNELS, true) ? $channel : 'email';
    }
}
