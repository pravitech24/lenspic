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
use Illuminate\Support\Facades\Gate;
use App\Models\Group;
use App\Models\User;
use App\Policies\GroupPolicy;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(\Twilio\Http\Client::class, \App\Services\Otp\PrivateTwilioTransport::class);
        $this->app->bind(\Twilio\Rest\Client::class, fn ($app) => new \Twilio\Rest\Client(
            config('services.twilio.account_sid'), config('services.twilio.auth_token'),
            config('services.twilio.account_sid'), null, $app->make(\Twilio\Http\Client::class),
            ['TWILIO_LOG_LEVEL'=>'error']
        ));
        $this->app->singleton(ProtectedMediaStorage::class, LaravelProtectedMediaStorage::class);
        $this->app->singleton(\Illuminate\Contracts\Redis\Factory::class, function ($app) { $config = $app['config']->get('database.redis', []); $client = \Illuminate\Support\Arr::pull($config, 'client', 'predis'); return new \Illuminate\Redis\RedisManager($app, $client, $config); });
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
    public function boot(): void
    {
        Gate::policy(Group::class, GroupPolicy::class);
        Gate::define('groups.create', function(User$user):bool{if($user->hasGroupCreatorRole())return true;$owner=app(\App\Services\Team\TeamAuthorization::class)->ownerFor($user);return$owner&&app(\App\Services\Team\TeamAuthorization::class)->allows($user,$owner,'create_groups');});
        foreach (['roles.view', 'roles.assign', 'roles.assign_super_admin', 'permissions.assign'] as $permission) {
            Gate::define($permission, fn (User $user): bool => $user->isSuperAdmin());
        }
        Event::listen(Login::class, fn (Login $event) => $event->user->studioMemberships()->where('status','active')->update(['last_active_at'=>now()]));
        \App\Services\Notifications\NotificationDomainSubscriber::register();
    }
}
