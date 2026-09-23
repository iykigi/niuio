<?php

use App\Models\Server;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    seedRolesAndPermissions();
});

it('seeds every default role with its documented permission set', function () {
    foreach (RolesAndPermissionsSeeder::ROLE_PERMISSIONS as $roleName => $permissions) {
        $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
        expect($role)->not->toBeNull();

        if ($permissions === ['*']) {
            expect($role->permissions()->count())->toBe(\Spatie\Permission\Models\Permission::count());
        } else {
            expect($role->permissions()->pluck('name')->sort()->values()->all())
                ->toBe(collect($permissions)->sort()->values()->all());
        }
    }
});

it('lets a Super Admin bypass every policy check regardless of assigned permissions', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('Super Admin');

    $server = Server::factory()->create();

    // ServerPolicy::update requires the 'servers.manage' permission, which
    // the Super Admin never needs directly — Gate::before short-circuits it.
    expect($superAdmin->can('update', $server))->toBeTrue();
    expect($superAdmin->can('servers.manage'))->toBeTrue();
});

it('does not let a plain user reach any admin ability', function () {
    $user = User::factory()->create();
    $user->assignRole('User');

    expect($user->can('users.view'))->toBeFalse();
    expect($user->can('servers.manage'))->toBeFalse();
    expect($user->can('settings.manage'))->toBeFalse();
});

it('gives Support staff read access to users but not the ability to change roles', function () {
    $support = User::factory()->create();
    $support->assignRole('Support');

    expect($support->can('users.view'))->toBeTrue();
    expect($support->can('users.change_role'))->toBeFalse();
    expect($support->can('support.manage'))->toBeTrue();
});

it('gives a Moderator the ability to suspend users and sites but not manage servers', function () {
    $moderator = User::factory()->create();
    $moderator->assignRole('Moderator');

    expect($moderator->can('users.suspend'))->toBeTrue();
    expect($moderator->can('sites.suspend'))->toBeTrue();
    expect($moderator->can('servers.manage'))->toBeFalse();
});
