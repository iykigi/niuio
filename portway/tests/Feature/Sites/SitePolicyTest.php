<?php

use App\Models\Site;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    seedRolesAndPermissions();
});

it('lets the owner view, update, and delete their own website', function () {
    $owner = User::factory()->create();
    $site = Site::factory()->create(['user_id' => $owner->id]);

    expect($owner->can('view', $site))->toBeTrue();
    expect($owner->can('update', $site))->toBeTrue();
    expect($owner->can('delete', $site))->toBeTrue();
});

it('does not let another regular user view, update, or delete someone else\'s website', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $site = Site::factory()->create(['user_id' => $owner->id]);

    expect($stranger->can('view', $site))->toBeFalse();
    expect($stranger->can('update', $site))->toBeFalse();
    expect($stranger->can('delete', $site))->toBeFalse();
});

it('blocks a suspended user from creating a new website', function () {
    $user = User::factory()->suspended()->create();

    expect($user->can('create', Site::class))->toBeFalse();
});

it('lets staff with the sites.manage permission manage any website', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $site = Site::factory()->create();

    expect($admin->can('view', $site))->toBeTrue();
    expect($admin->can('update', $site))->toBeTrue();
    expect($admin->can('delete', $site))->toBeTrue();
});

it('refuses to render the website detail page for a non-owner', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $site = Site::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($stranger);

    Livewire::test(\App\Livewire\Sites\SiteShow::class, ['site' => $site])
        ->assertStatus(403);
});

it('renders the website detail page for its owner', function () {
    $owner = User::factory()->create();
    $site = Site::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner);

    Livewire::test(\App\Livewire\Sites\SiteShow::class, ['site' => $site])
        ->assertOk()
        ->assertSee($site->name);
});
