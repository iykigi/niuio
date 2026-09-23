<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Granular permissions, grouped by module. Every nav section in the
     * admin panel gates on one or more of these rather than on a role
     * name directly, so a Super Admin can later create a custom role
     * (e.g. "Billing-only Support") with an arbitrary subset.
     */
    public const PERMISSIONS = [
        'users' => ['users.view', 'users.edit', 'users.suspend', 'users.delete', 'users.impersonate', 'users.change_role'],
        'sites' => ['sites.view', 'sites.manage', 'sites.suspend'],
        'domains' => ['domains.view', 'domains.manage'],
        'databases' => ['databases.view', 'databases.manage'],
        'backups' => ['backups.view', 'backups.manage'],
        'releases' => ['releases.view', 'releases.manage'],
        'servers' => ['servers.view', 'servers.manage'],
        'security' => ['security.view', 'security.manage'],
        'support' => ['support.view', 'support.manage'],
        'announcements' => ['announcements.view', 'announcements.manage'],
        'settings' => ['settings.view', 'settings.manage'],
        'activity_logs' => ['activity_logs.view'],
        'roles' => ['roles.view', 'roles.manage'],
    ];

    public const ROLE_PERMISSIONS = [
        'Super Admin' => ['*'], // Gate::before short-circuits this role anyway.
        'Admin' => [
            'users.view', 'users.edit', 'users.suspend', 'users.change_role',
            'sites.view', 'sites.manage', 'sites.suspend',
            'domains.view', 'domains.manage',
            'databases.view', 'databases.manage',
            'backups.view', 'backups.manage',
            'releases.view', 'releases.manage',
            'servers.view', 'servers.manage',
            'security.view', 'security.manage',
            'support.view', 'support.manage',
            'announcements.view', 'announcements.manage',
            'settings.view', 'settings.manage',
            'activity_logs.view',
            'roles.view',
        ],
        'Support' => [
            'users.view',
            'sites.view',
            'domains.view',
            'databases.view',
            'backups.view',
            'releases.view',
            'security.view',
            'support.view', 'support.manage',
            'activity_logs.view',
        ],
        'Moderator' => [
            'users.view', 'users.suspend',
            'sites.view', 'sites.suspend',
            'security.view', 'security.manage',
            'support.view', 'support.manage',
        ],
        'User' => [],
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permissions) {
            foreach ($permissions as $permission) {
                Permission::findOrCreate($permission, 'web');
            }
        }

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');

            if ($permissions === ['*']) {
                $role->syncPermissions(Permission::all());
            } else {
                $role->syncPermissions($permissions);
            }
        }
    }
}
