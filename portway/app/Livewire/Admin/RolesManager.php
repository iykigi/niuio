<?php

namespace App\Livewire\Admin;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
class RolesManager extends Component
{
    /** Roles seeded at install time — protected from deletion and renaming. */
    public const BUILT_IN_ROLES = ['Super Admin', 'Admin', 'Support', 'Moderator', 'User'];

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    /** @var array<int, string> */
    public array $selectedPermissions = [];

    public ?int $deleteTargetId = null;

    public function mount(): void
    {
        $this->authorize('roles.view');
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'name', 'selectedPermissions']);
        $this->showForm = true;
    }

    public function openEdit(int $roleId): void
    {
        $this->authorize('roles.manage');

        $role = Role::findOrFail($roleId);

        $this->editingId = $role->id;
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->all();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('roles.manage');

        $this->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('roles', 'name')->ignore($this->editingId)],
            'selectedPermissions' => ['array'],
            'selectedPermissions.*' => ['string', 'exists:permissions,name'],
        ]);

        if ($this->editingId) {
            $role = Role::findOrFail($this->editingId);

            if (in_array($role->name, self::BUILT_IN_ROLES, true) && $role->name !== $this->name) {
                $this->addError('name', 'Built-in roles cannot be renamed.');

                return;
            }
        } else {
            $role = Role::create(['name' => $this->name, 'guard_name' => 'web']);
        }

        // Super Admin always keeps every permission — Gate::before already
        // bypasses checks for it, but the role record should reflect that.
        $permissions = $role->name === 'Super Admin'
            ? Permission::all()
            : $this->selectedPermissions;

        $role->syncPermissions($permissions);

        $this->showForm = false;
        $this->dispatch('toast', message: 'Role saved.', level: 'success');
    }

    public function confirmDelete(int $roleId): void
    {
        $this->deleteTargetId = $roleId;
    }

    public function delete(): void
    {
        $this->authorize('roles.manage');

        $role = Role::findOrFail($this->deleteTargetId);

        if (in_array($role->name, self::BUILT_IN_ROLES, true)) {
            $this->dispatch('toast', message: 'Built-in roles cannot be deleted.', level: 'danger');
            $this->deleteTargetId = null;

            return;
        }

        if ($role->users()->exists()) {
            $this->dispatch('toast', message: 'Reassign the users on this role before deleting it.', level: 'danger');
            $this->deleteTargetId = null;

            return;
        }

        $role->delete();
        $this->deleteTargetId = null;
        $this->dispatch('toast', message: 'Role deleted.', level: 'success');
    }

    public function render()
    {
        return view('livewire.admin.roles-manager', [
            'roles' => Role::withCount('users')->orderBy('name')->get(),
            'permissionGroups' => RolesAndPermissionsSeeder::PERMISSIONS,
        ])->title('Roles · Admin · Portway');
    }
}
