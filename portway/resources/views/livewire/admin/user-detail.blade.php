<div class="space-y-6">
    <div>
        <a href="{{ route('admin.users.index') }}" wire:navigate class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">&larr; Back to users</a>
        <div class="mt-1 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-slate-900 dark:text-white">{{ $user->name }}</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $user->email }} &middot; joined {{ $user->created_at->format('M j, Y') }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if ($user->is_suspended)
                    <span class="badge-danger">Suspended</span>
                    <button wire:click="unsuspend" class="btn-secondary !py-1.5 text-sm">Reinstate</button>
                @else
                    <span class="badge-success">Active</span>
                    @if ($user->id !== auth()->id() && ! $user->hasRole('Super Admin'))
                        <button wire:click="confirmSuspend" class="btn-secondary !py-1.5 text-sm">Suspend</button>
                    @endif
                @endif

                @can('impersonate', $user)
                    <form method="POST" action="{{ route('admin.users.impersonate', $user) }}">
                        @csrf
                        <button type="submit" class="btn-secondary !py-1.5 text-sm">View as user</button>
                    </form>
                @endcan

                @if ($user->id !== auth()->id() && ! $user->hasRole('Super Admin'))
                    <button wire:click="confirmDelete" class="btn-ghost !py-1.5 text-sm text-rose-600">Delete</button>
                @endif
            </div>
        </div>
    </div>

    @if ($user->is_suspended && $user->suspension_reason)
        <div class="rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">
            Suspension reason: {{ $user->suspension_reason }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="card">
            <h3 class="mb-4 font-semibold text-slate-800 dark:text-slate-100">Role</h3>
            <div class="flex items-end gap-2">
                <select wire:model="selectedRole" class="input" @disabled($user->hasRole('Super Admin'))>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}">{{ $role }}</option>
                    @endforeach
                </select>
                @unless ($user->hasRole('Super Admin'))
                    <button wire:click="changeRole" class="btn-secondary">Save</button>
                @endunless
            </div>
        </div>

        <div class="card">
            <h3 class="mb-2 font-semibold text-slate-800 dark:text-slate-100">Account overview</h3>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-slate-400">Websites</dt><dd class="font-medium text-slate-700 dark:text-slate-200">{{ $sites->count() }}</dd></div>
                <div><dt class="text-slate-400">Domains</dt><dd class="font-medium text-slate-700 dark:text-slate-200">{{ $domains->count() }}</dd></div>
                <div><dt class="text-slate-400">Last seen</dt><dd class="font-medium text-slate-700 dark:text-slate-200">{{ $user->last_seen_at?->diffForHumans() ?? 'Never' }}</dd></div>
                <div><dt class="text-slate-400">Two-factor</dt><dd class="font-medium text-slate-700 dark:text-slate-200">{{ $user->hasTwoFactorEnabled() ? 'Enabled' : 'Disabled' }}</dd></div>
            </dl>
        </div>
    </div>

    <div class="card">
        <h3 class="mb-4 font-semibold text-slate-800 dark:text-slate-100">Quotas &amp; limits</h3>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div><label class="label">Storage (MB)</label><input type="number" wire:model="storageQuotaMb" class="input"></div>
            <div><label class="label">Bandwidth (MB/mo)</label><input type="number" wire:model="bandwidthQuotaMb" class="input"></div>
            <div><label class="label">Max websites</label><input type="number" wire:model="maxWebsites" class="input"></div>
            <div><label class="label">Max databases</label><input type="number" wire:model="maxDatabases" class="input"></div>
            <div><label class="label">Max domains</label><input type="number" wire:model="maxDomains" class="input"></div>
            <div><label class="label">Max cron jobs</label><input type="number" wire:model="maxCronJobs" class="input"></div>
            <div><label class="label">Max backups</label><input type="number" wire:model="maxBackups" class="input"></div>
            <div><label class="label">Max email accounts</label><input type="number" wire:model="maxEmailAccounts" class="input"></div>
        </div>
        <button wire:click="saveQuotas" class="btn-primary mt-4">Save quotas</button>
    </div>

    <div class="card">
        <h3 class="mb-3 font-semibold text-slate-800 dark:text-slate-100">Websites</h3>
        @if ($sites->isEmpty())
            <p class="text-sm text-slate-400">No websites yet.</p>
        @else
            <ul class="divide-y divide-surface-100 dark:divide-white/5">
                @foreach ($sites as $site)
                    <li class="flex items-center justify-between py-2.5 text-sm">
                        <span class="font-medium text-slate-700 dark:text-slate-200">{{ $site->name }}</span>
                        <span class="text-slate-400">{{ $site->diskUsageHuman() }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    @can('security.view')
        <div class="card">
            <h3 class="mb-3 font-semibold text-slate-800 dark:text-slate-100">Recent sign-in activity</h3>
            @if ($loginAttempts->isEmpty())
                <p class="text-sm text-slate-400">No sign-in activity recorded.</p>
            @else
                <ul class="divide-y divide-surface-100 dark:divide-white/5">
                    @foreach ($loginAttempts as $attempt)
                        <li class="flex items-center justify-between py-2 text-sm">
                            <span class="{{ $attempt->successful ? 'badge-success' : 'badge-danger' }}">{{ $attempt->successful ? 'Success' : 'Failed' }}</span>
                            <span class="font-mono text-slate-500 dark:text-slate-400">{{ $attempt->ip_address }}</span>
                            <span class="text-slate-400">{{ $attempt->created_at->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="card">
            <h3 class="mb-1 font-semibold text-slate-800 dark:text-slate-100">Platform-wide IP blocks</h3>
            <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Blocks this account's access from these addresses platform-wide (e.g. for abuse response).</p>

            @can('security.manage')
                <div class="mb-4 flex flex-wrap items-end gap-2">
                    <div>
                        <label class="label">IP address</label>
                        <input type="text" wire:model="blockIp" class="input font-mono text-sm">
                        @error('blockIp') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex-1">
                        <label class="label">Reason</label>
                        <input type="text" wire:model="blockReason" class="input">
                    </div>
                    <button wire:click="addBlockedIp" class="btn-secondary">Block</button>
                </div>
            @endcan

            @if ($blockedIps->isEmpty())
                <p class="text-sm text-slate-400">No platform-wide blocks on this account.</p>
            @else
                <ul class="divide-y divide-surface-100 dark:divide-white/5">
                    @foreach ($blockedIps as $blocked)
                        <li class="flex items-center justify-between py-2 text-sm">
                            <span class="font-mono text-slate-700 dark:text-slate-200">{{ $blocked->ip_address }}</span>
                            <span class="text-slate-400">{{ $blocked->reason }}</span>
                            @can('security.manage')
                                <button wire:click="removeBlockedIp({{ $blocked->id }})" class="btn-ghost !py-1 !px-2 text-xs text-rose-600">Remove</button>
                            @endcan
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endcan

    <div class="card">
        <h3 class="mb-3 font-semibold text-slate-800 dark:text-slate-100">Activity log</h3>
        @if ($activity->isEmpty())
            <p class="text-sm text-slate-400">No activity recorded yet.</p>
        @else
            <ul class="divide-y divide-surface-100 dark:divide-white/5">
                @foreach ($activity as $entry)
                    <li class="py-2 text-sm">
                        <span class="text-slate-700 dark:text-slate-200">{{ $entry->description ?: $entry->action }}</span>
                        <span class="ml-2 text-xs text-slate-400">{{ $entry->created_at->diffForHumans() }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <x-modal :show="$confirmingSuspend" wire:click.outside="$set('confirmingSuspend', false)">
        <h3 class="mb-2 font-semibold text-slate-800 dark:text-slate-100">Suspend this account?</h3>
        <label class="label">Reason (shown to the user)</label>
        <textarea wire:model="suspensionReason" rows="3" class="input"></textarea>
        <div class="mt-4 flex justify-end gap-2">
            <button wire:click="$set('confirmingSuspend', false)" class="btn-secondary">Cancel</button>
            <button wire:click="suspend" class="btn-danger">Suspend</button>
        </div>
    </x-modal>

    <x-modal :show="$confirmingDelete" wire:click.outside="$set('confirmingDelete', false)">
        <h3 class="mb-2 font-semibold text-slate-800 dark:text-slate-100">Delete this account?</h3>
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">This is a serious, hard-to-reverse action.</p>
        <div class="flex justify-end gap-2">
            <button wire:click="$set('confirmingDelete', false)" class="btn-secondary">Cancel</button>
            <button wire:click="delete" class="btn-danger">Delete account</button>
        </div>
    </x-modal>
</div>
