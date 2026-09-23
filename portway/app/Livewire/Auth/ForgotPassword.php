<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class ForgotPassword extends Component
{
    public string $email = '';

    public ?string $status = null;

    public function sendResetLink(): void
    {
        $this->validate(['email' => ['required', 'email']]);

        Password::sendResetLink(['email' => $this->email]);

        // Always show the same message, whether or not the address
        // exists — never confirm or deny an account by email address.
        $this->status = 'If an account exists for that address, a password reset link is on its way.';
    }

    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}
