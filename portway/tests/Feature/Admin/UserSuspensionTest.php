<?php

use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    seedRolesAndPermissions();
});

it('lets an Admin suspend a regular user, blocking them from the app', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $target = User::factory()->create();
    $target->assignRole('User');

    $this->actingAs($admin);

    Livewire::test(\App\Livewire\Admin\UsersManager::class)
        ->call('confirmSuspend', $target->id)
        ->set('suspensionReason', 'Abuse report confirmed')
        ->call('suspend');

    $target->refresh();
    expect($target->is_suspended)->toBeTrue();
    expect($target->suspension_reason)->toBe('Abuse report confirmed');

    $this->actingAs($target)
        ->get(route('dashboard'))
        ->assertRedirect(route('suspended'));
});

it('does not let a Moderator delete or suspend a Super Admin', function () {
    $moderator = User::factory()->create();
    $moderator->assignRole('Moderator');

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('Super Admin');

    expect($moderator->can('suspend', $superAdmin))->toBeFalse();
    expect($moderator->can('delete', $superAdmin))->toBeFalse();
});

it('does not let staff without the users.view permission open the admin panel', function () {
    $plainUser = User::factory()->create();
    $plainUser->assignRole('User');

    $this->actingAs($plainUser)
        ->get(route('admin.overview'))
        ->assertForbidden();
});

it('lets a suspended user reach support but nothing else in the app', function () {
    $user = User::factory()->suspended()->create();
    $user->assignRole('User');

    $this->actingAs($user);

    $this->get(route('dashboard'))->assertRedirect(route('suspended'));
    $this->get(route('support.index'))->assertOk();
});
