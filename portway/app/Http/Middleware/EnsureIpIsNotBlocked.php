<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces the blocked-IP records a user (Security Center, "account"
 * scope) or a staff member (Admin > User, "platform" scope) creates
 * against App\Models\BlockedIp. Both flows only ever persist rows —
 * nothing previously checked them against an incoming request, which
 * made IP blocking purely cosmetic. This runs on every authenticated
 * request and immediately ends the session the moment its owner's
 * current IP matches one of their own non-expired blocks.
 */
class EnsureIpIsNotBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $ip = $request->ip();

        if ($user && $ip && $this->isBlocked($user, $ip)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, 'Access from this IP address has been blocked.');
        }

        return $next($request);
    }

    private function isBlocked(User $user, string $ip): bool
    {
        return BlockedIp::query()
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->get()
            ->contains(fn (BlockedIp $blocked) => $this->matches($blocked, $ip));
    }

    private function matches(BlockedIp $blocked, string $ip): bool
    {
        if ($blocked->cidr) {
            return $this->ipInCidr($ip, $blocked->cidr);
        }

        return hash_equals((string) $blocked->ip_address, $ip);
    }

    /**
     * IPv4-only CIDR match. A malformed or non-IPv4 value (including an
     * IPv6 CIDR, not supported here) simply never matches rather than
     * throwing, so a bad admin-entered value can't take down every
     * request for the account.
     */
    private function ipInCidr(string $ip, string $cidr): bool
    {
        if (! str_contains($cidr, '/')) {
            return hash_equals($cidr, $ip);
        }

        [$subnet, $bits] = explode('/', $cidr, 2);
        $bits = (int) $bits;

        if ($bits < 0 || $bits > 32) {
            return false;
        }

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = $bits === 0 ? 0 : (~0 << (32 - $bits));

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}
