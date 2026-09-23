<?php

namespace App\Livewire\Auth;

use App\Models\LoginAttempt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;
use PragmaRX\Google2FA\Google2FA;

#[Layout('layouts.guest')]
class TwoFactorChallenge extends Component
{
    public string $code = '';

    public string $recoveryCode = '';

    public bool $useRecoveryCode = false;

    public function verify(): void
    {
        $user = Auth::user();
        $key = "2fa-challenge:{$user->id}";

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('code', 'Too many attempts. Please wait a minute and try again.');

            return;
        }

        if ($this->useRecoveryCode) {
            $this->verifyRecoveryCode($user);

            return;
        }

        $this->validate(['code' => ['required', 'digits:6']]);

        $valid = (new Google2FA)->verifyKey($user->two_factor_secret, $this->code);

        if (! $valid) {
            RateLimiter::hit($key, 60);
            $this->addError('code', 'That code is invalid or has expired.');

            LoginAttempt::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => request()->ip(),
                'user_agent' => (string) request()->userAgent(),
                'successful' => false,
                'failure_reason' => '2fa_failed',
            ]);

            return;
        }

        RateLimiter::clear($key);
        session(['two_factor_passed' => true]);
        $this->redirectRoute('dashboard', navigate: true);
    }

    private function verifyRecoveryCode($user): void
    {
        $this->validate(['recoveryCode' => ['required', 'string']]);

        $codes = $user->two_factor_recovery_codes ?? [];

        if (! in_array($this->recoveryCode, $codes, true)) {
            RateLimiter::hit("2fa-challenge:{$user->id}", 60);
            $this->addError('recoveryCode', 'That recovery code is invalid.');

            return;
        }

        $user->forceFill([
            'two_factor_recovery_codes' => array_values(array_diff($codes, [$this->recoveryCode])),
        ])->save();

        session(['two_factor_passed' => true]);
        $this->redirectRoute('dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.two-factor-challenge');
    }
}
