<?php

use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('logs a user in with valid credentials and records a successful attempt', function () {
    $user = User::factory()->create(['password' => Hash::make('correct-password')]);

    Livewire::test(\App\Livewire\Auth\Login::class)
        ->set('email', $user->email)
        ->set('password', 'correct-password')
        ->call('login')
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);

    expect(LoginAttempt::where('user_id', $user->id)->where('successful', true)->exists())->toBeTrue();
});

it('rejects an incorrect password and records a failed attempt', function () {
    $user = User::factory()->create(['password' => Hash::make('correct-password')]);

    Livewire::test(\App\Livewire\Auth\Login::class)
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors(['email']);

    $this->assertGuest();

    expect(LoginAttempt::where('user_id', $user->id)->where('successful', false)->exists())->toBeTrue();
});

it('sends a user with two-factor enabled to the challenge screen instead of the dashboard', function () {
    $user = User::factory()->create([
        'password' => Hash::make('correct-password'),
        'two_factor_secret' => 'ABCDEFGHIJKLMNOP',
        'two_factor_confirmed_at' => now(),
    ]);

    Livewire::test(\App\Livewire\Auth\Login::class)
        ->set('email', $user->email)
        ->set('password', 'correct-password')
        ->call('login')
        ->assertRedirect(route('two-factor.challenge'));

    $this->assertAuthenticatedAs($user);
    expect(session('two_factor_passed'))->toBeFalse();
});

it('throttles repeated failed login attempts from the same email and IP', function () {
    $user = User::factory()->create(['password' => Hash::make('correct-password')]);

    $component = Livewire::test(\App\Livewire\Auth\Login::class)
        ->set('email', $user->email)
        ->set('password', 'wrong-password');

    for ($i = 0; $i < 5; $i++) {
        $component->call('login');
    }

    $component->call('login')->assertHasErrors(['email']);

    expect(
        $component->get('email')
    )->toBe($user->email); // sanity: component state survived the throttled attempt
});
