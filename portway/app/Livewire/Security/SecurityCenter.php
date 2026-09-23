<?php

namespace App\Livewire\Security;

use App\Models\BlockedIp;
use App\Models\LoginAttempt;
use App\Services\Security\TwoFactorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SecurityCenter extends Component
{
    // --- Two-factor setup flow --------------------------------------------
    public bool $settingUp2fa = false;

    public string $pendingSecret = '';

    public string $confirmationCode = '';

    /** @var array<int, string> */
    public array $freshRecoveryCodes = [];

    public bool $showRecoveryCodes = false;

    public string $disableConfirmPassword = '';

    public bool $confirmingDisable = false;

    // --- Password change ---------------------------------------------------
    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPassword_confirmation = '';

    // --- IP blocking (account scope) ---------------------------------------
    public string $blockIp = '';

    public string $blockReason = '';

    // --- Sessions ------------------------------------------------------------
    public bool $confirmingSignOutOthers = false;

    public string $signOutPassword = '';

    public function mount(): void
    {
        //
    }

    // --- Two-factor ----------------------------------------------------------

    public function beginTwoFactorSetup(TwoFactorService $service): void
    {
        $this->pendingSecret = $service->generateSecretKey();
        $this->settingUp2fa = true;
        $this->confirmationCode = '';
    }

    public function cancelTwoFactorSetup(): void
    {
        $this->settingUp2fa = false;
        $this->pendingSecret = '';
        $this->confirmationCode = '';
    }

    public function confirmTwoFactorSetup(TwoFactorService $service): void
    {
        $this->validate(['confirmationCode' => ['required', 'digits:6']]);

        if (! $service->verify($this->pendingSecret, $this->confirmationCode)) {
            $this->addError('confirmationCode', 'That code did not match. Check the time on your device and try again.');

            return;
        }

        $this->freshRecoveryCodes = $service->confirm(Auth::user(), $this->pendingSecret);

        $this->settingUp2fa = false;
        $this->pendingSecret = '';
        $this->confirmationCode = '';
        $this->showRecoveryCodes = true;

        $this->dispatch('toast', message: 'Two-factor authentication is now enabled.', level: 'success');
    }

    public function confirmDisableTwoFactor(): void
    {
        $this->confirmingDisable = true;
    }

    public function disableTwoFactor(TwoFactorService $service): void
    {
        $this->validate(['disableConfirmPassword' => ['required', 'string']]);

        if (! Hash::check($this->disableConfirmPassword, Auth::user()->password)) {
            $this->addError('disableConfirmPassword', 'That password is incorrect.');

            return;
        }

        $service->disable(Auth::user());
        $this->confirmingDisable = false;
        $this->disableConfirmPassword = '';

        $this->dispatch('toast', message: 'Two-factor authentication has been disabled.', level: 'success');
    }

    public function regenerateRecoveryCodes(TwoFactorService $service): void
    {
        $this->freshRecoveryCodes = $service->regenerateRecoveryCodes(Auth::user());
        $this->showRecoveryCodes = true;

        $this->dispatch('toast', message: 'New recovery codes generated. Save them somewhere safe.', level: 'success');
    }

    public function closeRecoveryCodes(): void
    {
        $this->showRecoveryCodes = false;
        $this->freshRecoveryCodes = [];
    }

    // --- Password --------------------------------------------------------

    public function changePassword(): void
    {
        $this->validate([
            'currentPassword' => ['required', 'string'],
            'newPassword' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()],
        ]);

        if (! Hash::check($this->currentPassword, Auth::user()->password)) {
            $this->addError('currentPassword', 'That password is incorrect.');

            return;
        }

        Auth::user()->update(['password' => Hash::make($this->newPassword)]);

        $this->reset(['currentPassword', 'newPassword', 'newPassword_confirmation']);
        $this->dispatch('toast', message: 'Password updated.', level: 'success');
    }

    // --- IP blocking -------------------------------------------------------

    public function addBlockedIp(): void
    {
        $this->validate([
            'blockIp' => ['required', 'ip'],
            'blockReason' => ['nullable', 'string', 'max:255'],
        ]);

        BlockedIp::create([
            'user_id' => Auth::id(),
            'ip_address' => $this->blockIp,
            'reason' => $this->blockReason ?: null,
            'scope' => 'account',
            'created_by' => Auth::id(),
        ]);

        $this->reset(['blockIp', 'blockReason']);
        $this->dispatch('toast', message: 'IP address blocked from your websites.', level: 'success');
    }

    public function removeBlockedIp(int $blockedIpId): void
    {
        BlockedIp::where('user_id', Auth::id())->where('id', $blockedIpId)->delete();
        $this->dispatch('toast', message: 'IP block removed.', level: 'success');
    }

    // --- Sessions ----------------------------------------------------------

    public function confirmSignOutOthers(): void
    {
        $this->confirmingSignOutOthers = true;
    }

    public function signOutOtherSessions(): void
    {
        $this->validate(['signOutPassword' => ['required', 'string']]);

        if (! Hash::check($this->signOutPassword, Auth::user()->password)) {
            $this->addError('signOutPassword', 'That password is incorrect.');

            return;
        }

        Auth::logoutOtherDevices($this->signOutPassword);

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', Auth::id())
                ->where('id', '!=', session()->getId())
                ->delete();
        }

        $this->confirmingSignOutOthers = false;
        $this->signOutPassword = '';
        $this->dispatch('toast', message: 'You have been signed out everywhere else.', level: 'success');
    }

    public function render()
    {
        $sessions = collect();

        if (config('session.driver') === 'database') {
            $sessions = DB::table(config('session.table', 'sessions'))
                ->where('user_id', Auth::id())
                ->orderByDesc('last_activity')
                ->get()
                ->map(fn ($session) => (object) [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'user_agent' => $session->user_agent,
                    'is_current_device' => $session->id === session()->getId(),
                    'last_active' => \Illuminate\Support\Carbon::createFromTimestamp($session->last_activity),
                ]);
        }

        return view('livewire.security.security-center', [
            'loginAttempts' => LoginAttempt::where('user_id', Auth::id())->latest()->limit(15)->get(),
            'blockedIps' => BlockedIp::where('user_id', Auth::id())->where('scope', 'account')->latest()->get(),
            'sessions' => $sessions,
        ])->title('Security · Portway');
    }
}
