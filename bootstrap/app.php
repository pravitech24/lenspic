<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsurePlatformAdmin;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [\App\Http\Middleware\ProductionSecurity::class, \App\Http\Middleware\TrackUserActivity::class, \App\Http\Middleware\HandleInertiaRequests::class]);
        $middleware->alias(["platform.admin" => EnsurePlatformAdmin::class, "entitlement" => \App\Http\Middleware\RequireSubscriptionEntitlement::class, "analytics.access" => \App\Http\Middleware\RequireAnalyticsAccess::class]);
        $middleware->validateCsrfTokens(except: [
            'webhooks/whatsapp',
            'billing/razorpay/webhook',
            'billing/razorpay/wallet-webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontFlash(['otp', 'code', 'password', 'password_confirmation', 'current_password']);
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The server rejected this upload because the request was too large. Please retry the failed photo.',
                ], 413);
            }
        });
    })->create();
