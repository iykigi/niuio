<?php

namespace App\Livewire\Auth;

use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = strtolower($this->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Try again in {$seconds} seconds.",
            ]);
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($throttleKey, 60);

            LoginAttempt::create([
                'user_id' => User::where('email', $this->email)->value('id'),
                'email' => $this->email,
                'ip_address' => request()->ip(),
                'user_agent' => (string) request()->userAgent(),
                'successful' => false,
                'failure_reason' => 'invalid_credentials',
            ]);

            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        RateLimiter::clear($throttleKey);
        request()->session()->regenerate();

        $user = Auth::user();

        LoginAttempt::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
            'successful' => true,
        ]);

        if ($user->hasTwoFactorEnabled()) {
            session(['two_factor_passed' => false]);
            $this->redirectRoute('two-factor.challenge');

            return;
        }

        session(['two_factor_passed' => true]);
        $this->redirectRoute('dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
