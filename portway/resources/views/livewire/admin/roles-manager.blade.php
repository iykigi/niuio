<div>
    <h1 class="mb-1 text-xl font-semibold text-slate-900 dark:text-white">Admin panel</h1>
    <p class="mb-6 text-sm text-slate-500 dark:text-slate-400">Manage staff roles and exactly what each one can do.</p>

    @include('admin.partials.nav')

    <div class="mb-4 flex justify-end">
        <button wire:click="openCreate" class="btn-primary">New role</button>
    </div>

    <div class="card">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-surface-200 text-left text-slate-400 dark:border-white/10">
                    <th class="py-2 font-medium">Role</th>
                    <th class="py-2 font-medium">Permissions</th>
                    <th class="py-2 font-medium">Users</th>
                    <th class="py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100 dark:divide-white/5">
                @foreach ($roles as $role)
                    <tr>
                        <td class="py-3 font-medium text-slate-800 dark:text-slate-100">
                            {{ $role->name }}
                            @if (in_array($role->name, \App\Livewire\Admin\RolesManager::BUILT_IN_ROLES, true))
                                <span class="badge-neutral ml-1">Built-in</span>
                            @endif
                        </td>
                        <td class="py-3 text-slate-500 dark:text-slate-400">
                            {{ $role->name === 'Super Admin' ? 'All permissions' : $role->permissions->count().' permissions' }}
                        </td>
                        <td class="py-3 text-slate-500 dark:text-slate-400">{{ $role->users_count }}</td>
                        <td class="py-3 text-right">
                            <div class="flex justify-end gap-1">
                                @if ($role->name !== 'Super Admin')
                                    <button wire:click="openEdit({{ $role->id }})" class="btn-ghost !py-1 !px-2 text-xs">Edit</button>
                                @endif
                                @unless (in_array($role->name, \App\Livewire\Admin\RolesManager::BUILT_IN_ROLES, true))
                                    <button wire:click="confirmDelete({{ $role->id }})" class="btn-ghost !py-1 !px-2 text-xs text-rose-600">Delete</button>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <x-modal :show="$showForm" max-width="2xl" wire:click.outside="$set('showForm', false)">
        <h3 class="mb-4 font-semibold text-slate-800 dark:text-slate-100">{{ $editingId ? 'Edit role' : 'New role' }}</h3>

        <div class="mb-4">
            <label class="label">Role name</label>
            <input type="text" wire:model="name" class="input max-w-xs" @disabled($editingId && in_array($name, \App\Livewire\Admin\RolesManager::BUILT_IN_ROLES, true))>
            @error('name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <label class="label mb-2 block">Permissions</label>
        <div class="max-h-80 space-y-4 overflow-y-auto rounded-lg border border-surface-200 p-4 dark:border-white/10">
            @foreach ($permissionGroups as $group => $permissions)
                <div>
                    <p class="mb-1.5 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ str($group)->headline() }}</p>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @foreach ($permissions as $permission)
                            <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                                <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission }}" class="rounded border-surface-300 text-harbor-600">
                                {{ $permission }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-5 flex justify-end gap-2">
            <button wire:click="$set('showForm', false)" class="btn-secondary">Cancel</button>
            <button wire:click="save" class="btn-primary">Save role</button>
        </div>
    </x-modal>

    <x-modal :show="(bool) $deleteTargetId" wire:click.outside="$set('deleteTargetId', null)">
        <h3 class="mb-2 font-semibold text-slate-800 dark:text-slate-100">Delete this role?</h3>
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Users on this role must be reassigned first.</p>
        <div class="flex justify-end gap-2">
            <button wire:click="$set('deleteTargetId', null)" class="btn-secondary">Cancel</button>
            <button wire:click="delete" class="btn-danger">Delete</button>
        </div>
    </x-modal>
</div>
