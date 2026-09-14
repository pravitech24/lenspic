<?php

namespace App\Http\Middleware;

use App\Services\Analytics\AnalyticsAuthorization;
use Closure;
use Illuminate\Http\Request;

class RequireAnalyticsAccess
{
    public function handle(Request $request, Closure $next, string $action = 'view')
    {
        $actor = $request->user();
        abort_unless($actor, 401);
        $context = app(AnalyticsAuthorization::class)->context($actor, $action === 'export');
        abort_unless($context, 403, 'You do not have permission to access analytics.');

        if (! app(AnalyticsAuthorization::class)->entitled($context)) {
            if ($request->expectsJson()) return response()->json(['message' => 'Your current plan does not include Analytics.', 'code' => 'upgrade_required', 'upgrade_url' => route('settings.subscription')], 402);
            return redirect()->route('settings.subscription', ['feature' => 'analytics'])->with('error', 'Analytics requires a plan that includes this feature.');
        }

        $request->attributes->set('analytics_context', $context);
        return $next($request);
    }
}
