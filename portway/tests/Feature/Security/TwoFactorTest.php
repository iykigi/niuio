<?php

use App\Models\User;
use App\Services\Security\TwoFactorService;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;

it('enables two-factor authentication end to end through the security center', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Livewire::test(\App\Livewire\Security\SecurityCenter::class)
        ->call('beginTwoFactorSetup')
        ->assertSet('settingUp2fa', true);

    $pendingSecret = $component->get('pendingSecret');
    $validCode = (new Google2FA)->getCurrentOtp($pendingSecret);

    $component
        ->set('confirmationCode', $validCode)
        ->call('confirmTwoFactorSetup')
        ->assertSet('settingUp2fa', false)
        ->assertSet('showRecoveryCodes', true);

    $user->refresh();

    expect($user->hasTwoFactorEnabled())->toBeTrue();
    expect($user->two_factor_recovery_codes)->toHaveCount(8);
});

it('rejects an incorrect confirmation code when enabling two-factor', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Security\SecurityCenter::class)
        ->call('beginTwoFactorSetup')
        ->set('confirmationCode', '000000')
        ->call('confirmTwoFactorSetup')
        ->assertHasErrors(['confirmationCode']);

    expect($user->refresh()->hasTwoFactorEnabled())->toBeFalse();
});

it('disables two-factor authentication after confirming the account password', function () {
    $service = app(TwoFactorService::class);
    $user = User::factory()->create(['password' => bcrypt('correct-password')]);
    $secret = $service->generateSecretKey();
    $service->confirm($user, $secret);

    expect($user->refresh()->hasTwoFactorEnabled())->toBeTrue();

    $this->actingAs($user);

    Livewire::test(\App\Livewire\Security\SecurityCenter::class)
        ->call('confirmDisableTwoFactor')
        ->set('disableConfirmPassword', 'correct-password')
        ->call('disableTwoFactor')
        ->assertHasNoErrors();

    expect($user->refresh()->hasTwoFactorEnabled())->toBeFalse();
});

it('refuses to disable two-factor authentication with the wrong password', function () {
    $service = app(TwoFactorService::class);
    $user = User::factory()->create(['password' => bcrypt('correct-password')]);
    $service->confirm($user, $service->generateSecretKey());

    $this->actingAs($user);

    Livewire::test(\App\Livewire\Security\SecurityCenter::class)
        ->call('confirmDisableTwoFactor')
        ->set('disableConfirmPassword', 'not-the-password')
        ->call('disableTwoFactor')
        ->assertHasErrors(['disableConfirmPassword']);

    expect($user->refresh()->hasTwoFactorEnabled())->toBeTrue();
});

it('lets a user sign in with a one-time recovery code and consumes it', function () {
    $service = app(TwoFactorService::class);
    $user = User::factory()->create(['password' => bcrypt('correct-password')]);
    $codes = $service->confirm($user, $service->generateSecretKey());
    $code = $codes[0];

    $this->actingAs($user);
    session(['two_factor_passed' => false]);

    Livewire::test(\App\Livewire\Auth\TwoFactorChallenge::class)
        ->set('useRecoveryCode', true)
        ->set('recoveryCode', $code)
        ->call('verify')
        ->assertRedirect(route('dashboard'));

    expect(session('two_factor_passed'))->toBeTrue();
    expect($user->refresh()->two_factor_recovery_codes)->not->toContain($code);
});
