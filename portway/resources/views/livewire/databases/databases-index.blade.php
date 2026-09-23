<div>
    <div class="mb-6 flex items-center justify-between">
        <p class="text-sm text-slate-500 dark:text-slate-400">Create MySQL/MariaDB databases and manage them from a clean built-in interface.</p>
        <button wire:click="$set('showCreateModal', true)" class="btn-primary"><x-heroicon-o-plus class="h-4 w-4" /> Create Database</button>
    </div>

    @if ($justCreatedCredentials)
        <div class="card mb-6 border-emerald-200 dark:border-emerald-500/20">
            <h3 class="font-semibold text-emerald-700 dark:text-emerald-300">Database created — save these credentials</h3>
            <p class="mb-3 text-sm text-slate-500 dark:text-slate-400">The password is shown only once.</p>
            <dl class="grid grid-cols-2 gap-2 font-mono text-sm">
                <dt class="text-slate-400">Host</dt><dd>{{ $justCreatedCredentials['host'] }}</dd>
                <dt class="text-slate-400">Database</dt><dd>{{ $justCreatedCredentials['database'] }}</dd>
                <dt class="text-slate-400">Username</dt><dd>{{ $justCreatedCredentials['username'] }}</dd>
                <dt class="text-slate-400">Password</dt><dd>{{ $justCreatedCredentials['password'] }}</dd>
            </dl>
            <button wire:click="$set('justCreatedCredentials', null)" class="btn-secondary mt-4">Done</button>
        </div>
    @endif

    @if ($databases->isEmpty())
        <x-empty-state icon="circle-stack" title="No databases yet" description="Create a database in seconds — Portway generates a secure user and password for you.">
            <x-slot:action>
                <button wire:click="$set('showCreateModal', true)" class="btn-primary">Create Database</button>
            </x-slot:action>
        </x-empty-state>
    @else
        <div class="card overflow-hidden !p-0">
            <table class="w-full text-sm">
                <thead class="border-b border-surface-200 bg-surface-50 text-left text-xs uppercase tracking-wide text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-400">
                    <tr><th class="px-4 py-3">Database</th><th class="px-4 py-3">Website</th><th class="px-4 py-3">Size</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-surface-100 dark:divide-white/5">
                    @foreach ($databases as $database)
                        <tr wire:key="db-{{ $database->id }}">
                            <td class="px-4 py-3 font-mono text-xs text-slate-800 dark:text-slate-100">{{ $database->name }}</td>
                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $database->site?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $database->sizeHuman() }}</td>
                            <td class="px-4 py-3"><span class="badge-neutral">{{ ucfirst($database->status) }}</span></td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('databases.show', $database) }}" wire:navigate class="btn-secondary !px-3 !py-1.5 text-xs">Manage</a>
                                <button wire:click="delete({{ $database->id }})" wire:confirm="Delete this database permanently?" class="btn-ghost !p-1.5 text-rose-600"><x-heroicon-o-trash class="h-4 w-4" /></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <x-modal :show="$showCreateModal" wire:click.outside="$set('showCreateModal', false)">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Create database</h3>
        <div class="mt-4">
            <label class="label">Label</label>
            <input type="text" wire:model="label" class="input" placeholder="e.g. blog">
            @error('label') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div class="mt-4">
            <label class="label">Attach to website (optional)</label>
            <select wire:model="siteId" class="input">
                <option value="">None</option>
                @foreach ($sites as $site)
                    <option value="{{ $site->id }}">{{ $site->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mt-5 flex justify-end gap-2">
            <button wire:click="$set('showCreateModal', false)" class="btn-secondary">Cancel</button>
            <button wire:click="create" class="btn-primary">Create</button>
        </div>
    </x-modal>
</div>
