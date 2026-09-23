<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordLastSeenActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            // Cheap, non-blocking touch — at most once a minute per user.
            $cacheKey = "portway:last_seen_touch:{$user->id}";

            if (! cache()->has($cacheKey)) {
                $user->forceFill(['last_seen_at' => now(), 'last_seen_ip' => $request->ip()])->saveQuietly();
                cache()->put($cacheKey, true, 60);
            }
        }

        return $next($request);
    }
}
