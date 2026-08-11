<?php

namespace App\Providers;

use App\Contracts\OtpSender;
use App\Contracts\ProtectedMediaStorage;
use App\Services\Media\LaravelProtectedMediaStorage;
use App\Services\LogOtpSender;
use App\Services\SmsOtpSender;
use App\Services\WhatsAppOtpSender;
use InvalidArgumentException;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ProtectedMediaStorage::class, LaravelProtectedMediaStorage::class);
        $this->app->bind(OtpSender::class, function ($app) {
            if ($app['config']->get('otp.test_mode') && $app->environment('local', 'testing')) {
                return $app->make(LogOtpSender::class);
            }
            return match ($app['config']->get('otp.driver')) {
                'log' => $app->make(LogOtpSender::class),
                'whatsapp' => $app->make(WhatsAppOtpSender::class),
                'sms' => $app->make(SmsOtpSender::class),
                default => throw new InvalidArgumentException('Unsupported OTP_DRIVER value.'),
            };
        });
    }
    public function boot(): void {}
}
