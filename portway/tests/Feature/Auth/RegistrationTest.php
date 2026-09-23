<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    seedRolesAndPermissions();
});

it('registers a new account with no payment details required and no verification gate', function () {
    Livewire::test(\App\Livewire\Auth\Register::class)
        ->set('name', 'Ada Lovelace')
        ->set('email', 'ada@example.com')
        ->set('password', 'Sup3rSecret!')
        ->set('password_confirmation', 'Sup3rSecret!')
        ->set('terms', true)
        ->call('register')
        ->assertRedirect(route('onboarding'));

    $user = User::where('email', 'ada@example.com')->first();

    expect($user)->not->toBeNull();
    expect(Hash::check('Sup3rSecret!', $user->password))->toBeTrue();
    expect($user->hasRole('User'))->toBeTrue();
    expect($user->max_websites)->toBe(config('portway.defaults.max_websites'));

    $this->assertAuthenticatedAs($user);
});

it('rejects registration when passwords do not match', function () {
    Livewire::test(\App\Livewire\Auth\Register::class)
        ->set('name', 'Ada Lovelace')
        ->set('email', 'ada@example.com')
        ->set('password', 'Sup3rSecret!')
        ->set('password_confirmation', 'somethingElse!')
        ->set('terms', true)
        ->call('register')
        ->assertHasErrors(['password']);

    expect(User::where('email', 'ada@example.com')->exists())->toBeFalse();
});

it('rejects a duplicate email address', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    Livewire::test(\App\Livewire\Auth\Register::class)
        ->set('name', 'Someone Else')
        ->set('email', 'taken@example.com')
        ->set('password', 'Sup3rSecret!')
        ->set('password_confirmation', 'Sup3rSecret!')
        ->set('terms', true)
        ->call('register')
        ->assertHasErrors(['email']);
});

it('refuses registration when the terms checkbox is not accepted', function () {
    Livewire::test(\App\Livewire\Auth\Register::class)
        ->set('name', 'Ada Lovelace')
        ->set('email', 'ada@example.com')
        ->set('password', 'Sup3rSecret!')
        ->set('password_confirmation', 'Sup3rSecret!')
        ->set('terms', false)
        ->call('register')
        ->assertHasErrors(['terms']);
});

it('blocks registration entirely when the platform has closed registration', function () {
    config(['portway.features.registration_open' => false]);

    Livewire::test(\App\Livewire\Auth\Register::class)
        ->set('name', 'Ada Lovelace')
        ->set('email', 'ada@example.com')
        ->set('password', 'Sup3rSecret!')
        ->set('password_confirmation', 'Sup3rSecret!')
        ->set('terms', true)
        ->call('register')
        ->assertForbidden();
});
