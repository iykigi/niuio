<div>
    <h1 class="mb-1 text-xl font-semibold text-slate-900 dark:text-white">Admin panel</h1>
    <p class="mb-6 text-sm text-slate-500 dark:text-slate-400">Manage every account on the platform.</p>

    @include('admin.partials.nav')

    <div class="card">
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by name or email…" class="input max-w-xs">
            <select wire:model.live="roleFilter" class="input max-w-[10rem]">
                <option value="">All roles</option>
                @foreach ($roles as $role)
                    <option value="{{ $role }}">{{ $role }}</option>
                @endforeach
            </select>
            <select wire:model.live="statusFilter" class="input max-w-[10rem]">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-surface-200 text-left text-slate-400 dark:border-white/10">
                        <th class="py-2 font-medium">Account</th>
                        <th class="py-2 font-medium">Role</th>
                        <th class="py-2 font-medium">Websites</th>
                        <th class="py-2 font-medium">Joined</th>
                        <th class="py-2 font-medium">Status</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-100 dark:divide-white/5">
                    @foreach ($users as $user)
                        <tr>
                            <td class="py-3">
                                <a href="{{ route('admin.users.show', $user) }}" wire:navigate class="font-medium text-slate-700 hover:text-harbor-700 dark:text-slate-200">{{ $user->name }}</a>
                                <span class="block text-xs text-slate-400">{{ $user->email }}</span>
                            </td>
                            <td class="py-3 text-slate-500 dark:text-slate-400">{{ $user->getRoleNames()->join(', ') ?: '—' }}</td>
                            <td class="py-3 text-slate-500 dark:text-slate-400">{{ $user->sites_count }}</td>
                            <td class="py-3 text-slate-500 dark:text-slate-400">{{ $user->created_at->format('M j, Y') }}</td>
                            <td class="py-3">
                                @if ($user->is_suspended)
                                    <span class="badge-danger">Suspended</span>
                                @else
                                    <span class="badge-success">Active</span>
                                @endif
                            </td>
                            <td class="py-3 text-right">
                                <div class="flex justify-end gap-1">
                                    @if (! $user->is_suspended && $user->id !== auth()->id() && ! $user->hasRole('Super Admin'))
                                        <button wire:click="confirmSuspend({{ $user->id }})" class="btn-ghost !py-1 !px-2 text-xs">Suspend</button>
                                    @elseif ($user->is_suspended)
                                        <button wire:click="unsuspend({{ $user->id }})" class="btn-ghost !py-1 !px-2 text-xs">Reinstate</button>
                                    @endif
                                    @if ($user->id !== auth()->id() && ! $user->hasRole('Super Admin'))
                                        <button wire:click="confirmDelete({{ $user->id }})" class="btn-ghost !py-1 !px-2 text-xs text-rose-600">Delete</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $users->links() }}</div>
    </div>

    <x-modal :show="(bool) $suspendTargetId" wire:click.outside="$set('suspendTargetId', null)">
        <h3 class="mb-2 font-semibold text-slate-800 dark:text-slate-100">Suspend this account?</h3>
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Their websites stay intact but become inaccessible until reinstated.</p>
        <label class="label">Reason (shown to the user)</label>
        <textarea wire:model="suspensionReason" rows="3" class="input"></textarea>
        <div class="mt-4 flex justify-end gap-2">
            <button wire:click="$set('suspendTargetId', null)" class="btn-secondary">Cancel</button>
            <button wire:click="suspend" class="btn-danger">Suspend account</button>
        </div>
    </x-modal>

    <x-modal :show="(bool) $deleteTargetId" wire:click.outside="$set('deleteTargetId', null)">
        <h3 class="mb-2 font-semibold text-slate-800 dark:text-slate-100">Delete this account?</h3>
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">This is a serious action. The account and its ownership records will be removed.</p>
        <div class="flex justify-end gap-2">
            <button wire:click="$set('deleteTargetId', null)" class="btn-secondary">Cancel</button>
            <button wire:click="delete" class="btn-danger">Delete account</button>
        </div>
    </x-modal>
</div>
