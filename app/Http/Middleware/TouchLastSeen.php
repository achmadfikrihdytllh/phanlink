<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Heartbeat: every authenticated request counts as "active now". The write is skipped when the stored
 * value is under a minute old, so the app's polling does not hit the database on every call.
 */
class TouchLastSeen
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && (! $user->last_seen_at || $user->last_seen_at->lt(now()->subSeconds(60)) || $user->status !== 'online')) {
            $user->forceFill(['status' => 'online', 'last_seen_at' => now()])->saveQuietly();
        }

        return $next($request);
    }
}
