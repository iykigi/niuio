<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AccountSettings extends Component
{
    public string $name = '';

    public string $email = '';

    public string $timezone = 'UTC';

    public string $locale = 'en';

    // API tokens
    public string $tokenName = '';

    /** @var array<int, string> */
    public array $tokenAbilities = [];

    public ?string $plainTextToken = null;

    public bool $confirmingDeletion = false;

    public string $deleteConfirmPassword = '';

    public const AVAILABLE_ABILITIES = [
        'sites:read' => 'View websites',
        'sites:write' => 'Manage websites',
        'domains:read' => 'View domains',
        'domains:write' => 'Manage domains',
        'databases:read' => 'View databases',
        'databases:write' => 'Manage databases',
        'backups:read' => 'View backups',
        'backups:write' => 'Manage backups',
        'deployments:read' => 'View deployments',
        'deployments:write' => 'Trigger deployments',
    ];

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->timezone = $user->timezone;
        $this->locale = $user->locale;
    }

    public function updateProfile(): void
    {
        $user = Auth::user();

        $this->validate([
            'name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'timezone' => ['required', 'timezone'],
            'locale' => ['required', 'string', 'max:10'],
        ]);

        $emailChanged = $this->email !== $user->email;

        $user->update([
            'name' => $this->name,
            'email' => $this->email,
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'email_verified_at' => $emailChanged ? null : $user->email_verified_at,
        ]);

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
            $this->dispatch('toast', message: 'Profile updated. Please re-verify your new email address.', level: 'success');
        } else {
            $this->dispatch('toast', message: 'Profile updated.', level: 'success');
        }
    }

    public function createToken(): void
    {
        $this->validate([
            'tokenName' => ['required', 'string', 'max:100'],
            'tokenAbilities' => ['required', 'array', 'min:1'],
            'tokenAbilities.*' => ['in:'.implode(',', array_keys(self::AVAILABLE_ABILITIES))],
        ]);

        $token = Auth::user()->createToken($this->tokenName, $this->tokenAbilities);

        $this->plainTextToken = $token->plainTextToken;
        $this->tokenName = '';
        $this->tokenAbilities = [];
    }

    public function closeTokenReveal(): void
    {
        $this->plainTextToken = null;
    }

    public function revokeToken(int $tokenId): void
    {
        Auth::user()->tokens()->where('id', $tokenId)->delete();
        $this->dispatch('toast', message: 'API token revoked.', level: 'success');
    }

    public function confirmDeletion(): void
    {
        $this->confirmingDeletion = true;
    }

    public function deleteAccount(): void
    {
        $this->validate(['deleteConfirmPassword' => ['required', 'string']]);

        $user = Auth::user();

        if (! Hash::check($this->deleteConfirmPassword, $user->password)) {
            $this->addError('deleteConfirmPassword', 'That password is incorrect.');

            return;
        }

        Auth::logout();
        $user->delete();

        session()->invalidate();
        session()->regenerateToken();

        $this->redirectRoute('home', navigate: true);
    }

    public function render()
    {
        return view('livewire.settings.account-settings', [
            'tokens' => Auth::user()->tokens()->latest()->get(),
        ])->title('Account settings · Portway');
    }
}
