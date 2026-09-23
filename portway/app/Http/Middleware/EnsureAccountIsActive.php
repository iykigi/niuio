<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks a suspended account from doing anything except viewing the
 * suspension notice and their support tickets — their data stays intact
 * (see Admin > Users > Suspend), it just becomes read-only-adjacent.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->is_suspended && ! $request->routeIs('suspended', 'support.*', 'logout')) {
            return redirect()->route('suspended');
        }

        return $next($request);
    }
}
