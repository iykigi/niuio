<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Security\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Lets a permitted staff member briefly view the panel as a specific
 * user, to reproduce or troubleshoot an issue, without knowing or
 * changing that user's password. The original staff session is kept in
 * the session (never nested more than one level deep) so "Stop
 * impersonating" always returns to the real, authenticated account.
 */
class ImpersonationController extends Controller
{
    public function start(Request $request, User $target, ActivityLogger $logger): RedirectResponse
    {
        $this->authorize('impersonate', $target);

        if (session()->has('impersonator_id')) {
            abort(403, 'You are already impersonating a user. Stop that session first.');
        }

        $logger->log(
            'user.impersonate.start',
            $target,
            "Started impersonating {$target->email}",
        );

        $impersonatorId = Auth::id();

        // Regenerate the session id before the identity behind it
        // changes: this is a privilege-boundary crossing exactly like a
        // login, and skipping it would leave the panel open to session
        // fixation across that boundary.
        $request->session()->regenerate();

        session(['impersonator_id' => $impersonatorId]);
        Auth::login($target);
        session(['two_factor_passed' => true]);

        return redirect()->route('dashboard');
    }

    public function stop(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $impersonatorId = session('impersonator_id');

        abort_unless($impersonatorId, 403);

        $impersonator = User::findOrFail($impersonatorId);
        $impersonated = Auth::user();

        session()->forget('impersonator_id');
        $request->session()->regenerate();
        Auth::login($impersonator);
        session(['two_factor_passed' => true]);

        $logger->log(
            'user.impersonate.stop',
            $impersonated,
            "Stopped impersonating {$impersonated?->email}",
        );

        return redirect()->route('admin.users.show', $impersonated);
    }
}
