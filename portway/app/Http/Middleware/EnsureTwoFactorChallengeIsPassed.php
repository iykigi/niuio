<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * After password auth succeeds for a user with 2FA enabled, the session
 * is marked "authenticated but not yet challenged" until they submit a
 * valid TOTP code (see Auth\TwoFactorChallengeController). This
 * middleware enforces that gap on every route behind it.
 */
class EnsureTwoFactorChallengeIsPassed
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasTwoFactorEnabled() && ! $request->session()->get('two_factor_passed')) {
            return redirect()->route('two-factor.challenge');
        }

        return $next($request);
    }
}
