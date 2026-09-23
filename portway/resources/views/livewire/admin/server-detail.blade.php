<div class="space-y-6">
    <div>
        <a href="{{ route('admin.servers.index') }}" wire:navigate class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">&larr; Back to servers</a>
        <div class="mt-1 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-slate-900 dark:text-white">{{ $server->name }}</h1>
                <p class="font-mono text-sm text-slate-500 dark:text-slate-400">{{ $server->hostname }} &middot; {{ $server->ip_address }}</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="{{ $health?->badgeClass() ?? 'badge-neutral' }}">{{ $health?->label() ?? ucfirst($server->status) }}</span>
                <button wire:click="confirmDelete" class="btn-ghost text-sm text-rose-600">Remove node</button>
            </div>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-4">
        <x-stat-card label="Websites" :value="$server->current_sites.' / '.$server->max_sites" icon="globe-alt" />
        <x-stat-card label="CPU load" :value="($server->latestMetric?->cpu_percent ?? 0).'%'" icon="cpu-chip" />
        <x-stat-card label="Memory" :value="($server->latestMetric?->memory_percent ?? 0).'%'" icon="circle-stack" />
        <x-stat-card label="Disk" :value="($server->latestMetric?->disk_percent ?? 0).'%'" icon="server" />
    </div>

    @if ($metrics->isNotEmpty())
        <div class="card">
            <h3 class="mb-4 font-semibold text-slate-800 dark:text-slate-100">CPU load — last {{ $metrics->count() }} samples</h3>
            <div class="flex items-end gap-1" style="height: 120px;">
                @foreach ($metrics as $metric)
                    <div class="flex-1 rounded-t bg-harbor-500/80 dark:bg-harbor-400/70" style="height: {{ max(2, round($metric->cpu_percent)) }}%" title="{{ $metric->recorded_at->format('H:i') }} — {{ $metric->cpu_percent }}%"></div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="card max-w-lg">
        <h3 class="mb-4 font-semibold text-slate-800 dark:text-slate-100">Node settings</h3>
        <div class="space-y-4">
            <div>
                <label class="label">Status</label>
                <select wire:model="status" class="input">
                    <option value="pending">Pending</option>
                    <option value="online">Online</option>
                    <option value="degraded">Degraded</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="offline">Offline</option>
                </select>
            </div>
            <div>
                <label class="label">Max websites</label>
                <input type="number" wire:model="maxSites" class="input">
            </div>
        </div>
        <button wire:click="save" class="btn-primary mt-5">Save</button>
    </div>

    <div class="card">
        <h3 class="mb-3 font-semibold text-slate-800 dark:text-slate-100">Websites on this node</h3>
        @if ($sites->isEmpty())
            <p class="text-sm text-slate-400">No websites are hosted on this node yet.</p>
        @else
            <ul class="divide-y divide-surface-100 dark:divide-white/5">
                @foreach ($sites as $site)
                    <li class="flex items-center justify-between py-2.5 text-sm">
                        <div>
                            <span class="font-medium text-slate-700 dark:text-slate-200">{{ $site->name }}</span>
                            <span class="ml-2 text-slate-400">{{ $site->user->name }}</span>
                        </div>
                        <span class="text-slate-400">{{ $site->diskUsageHuman() }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <x-modal :show="$confirmingDelete" wire:click.outside="$set('confirmingDelete', false)">
        <h3 class="mb-2 font-semibold text-slate-800 dark:text-slate-100">Remove this hosting node?</h3>
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Nodes with websites still on them can't be removed.</p>
        <div class="flex justify-end gap-2">
            <button wire:click="$set('confirmingDelete', false)" class="btn-secondary">Cancel</button>
            <button wire:click="delete" class="btn-danger">Remove</button>
        </div>
    </x-modal>
</div>
