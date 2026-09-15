<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && (! $user->last_seen_at || $user->last_seen_at->lte(now()->subMinutes(5)))) {
            // Record activity without changing the profile's updated_at timestamp.
            $seenAt = now();
            $user->getConnection()->table($user->getTable())
                ->where($user->getKeyName(), $user->getKey())
                ->update(['last_seen_at' => $seenAt]);
            $user->last_seen_at = $seenAt;
        }

        return $next($request);
    }
}
