<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $terms = false;

    public function mount(): void
    {
        abort_unless(config('portway.features.registration_open'), 403, 'Registration is currently closed.');
    }

    public function register(): void
    {
        abort_unless(config('portway.features.registration_open'), 403, 'Registration is currently closed.');

        $this->validate([
            'name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()],
            'terms' => ['accepted'],
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        $user->assignRole('User');

        event(new Registered($user));

        Auth::login($user);
        session(['two_factor_passed' => true]);

        $this->redirectRoute('onboarding', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
