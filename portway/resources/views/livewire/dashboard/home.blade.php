<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Welcome back, {{ auth()->user()->name }}</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Here's what's happening across your hosting account.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('sites.create') }}" wire:navigate class="btn-primary">
                <x-heroicon-o-plus class="h-4 w-4" /> Create Website
            </a>
        </div>
    </div>

    {{-- Quick actions --}}
    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        @foreach ([
            ['label' => 'Create Website', 'route' => 'sites.create', 'icon' => 'plus-circle'],
            ['label' => 'Add Domain', 'route' => 'domains.index', 'icon' => 'link'],
            ['label' => 'Create Database', 'route' => 'databases.index', 'icon' => 'circle-stack'],
            ['label' => 'Open Terminal', 'route' => 'sites.index', 'params' => ['tool' => 'terminal'], 'icon' => 'command-line'],
            ['label' => 'Create Backup', 'route' => 'sites.index', 'params' => ['tool' => 'backups'], 'icon' => 'archive-box'],
            ['label' => 'Enable SSL', 'route' => 'domains.index', 'icon' => 'lock-closed'],
        ] as $action)
            <a href="{{ route($action['route'], $action['params'] ?? []) }}" wire:navigate class="card-flat flex flex-col items-center gap-2 p-4 text-center transition hover:shadow-soft">
                <span class="rounded-lg bg-harbor-50 p-2 text-harbor-600 dark:bg-harbor-500/10 dark:text-harbor-300">
                    <x-dynamic-component :component="'heroicon-o-'.$action['icon']" class="h-5 w-5" />
                </span>
                <span class="text-xs font-medium text-slate-600 dark:text-slate-300">{{ $action['label'] }}</span>
            </a>
        @endforeach
    </div>

    {{-- Stat grid --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card label="Websites" :value="$sites->count().' ('.$activeSitesCount.' active)'" icon="globe-alt" />
        <x-stat-card label="Domains" :value="$domainCount" icon="link" />
        <x-stat-card label="Databases" :value="$databaseCount" icon="circle-stack" />
        <x-stat-card label="Active SSL certificates" :value="$sslActiveCount" icon="lock-closed" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">Your websites</h3>
                <a href="{{ route('sites.index') }}" wire:navigate class="text-sm text-harbor-600 dark:text-harbor-400">View all</a>
            </div>

            @if ($sites->isEmpty())
                <x-empty-state icon="globe-alt" title="No websites yet" description="Create your first website in under a minute — no credit card required.">
                    <x-slot:action>
                        <a href="{{ route('sites.create') }}" wire:navigate class="btn-primary">Create Website</a>
                    </x-slot:action>
                </x-empty-state>
            @else
                <div class="divide-y divide-surface-100 dark:divide-white/5">
                    @foreach ($sites->take(6) as $site)
                        <a href="{{ route('sites.show', $site) }}" wire:navigate class="flex items-center justify-between gap-3 py-3">
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-surface-100 text-slate-500 dark:bg-white/5 dark:text-slate-400">
                                    <x-heroicon-o-globe-alt class="h-4 w-4" />
                                </span>
                                <div>
                                    <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ $site->name }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $site->domains->firstWhere('type', 'primary')?->hostname ?? $site->temporaryHostname() }}</p>
                                </div>
                            </div>
                            <span class="{{ $site->status->badgeClass() }}">{{ $site->status->label() }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="card">
                <h3 class="mb-3 font-semibold text-slate-800 dark:text-slate-100">Storage</h3>
                <div class="flex items-baseline justify-between text-sm">
                    <span class="font-medium text-slate-800 dark:text-slate-100">{{ $usedHuman }} / {{ $quotaHuman }}</span>
                    <span class="text-slate-500 dark:text-slate-400">{{ $usedPercent }}%</span>
                </div>
                <x-progress-bar :percent="$usedPercent" class="mt-2" />
                @if ($usedPercent >= 80)
                    <p class="mt-2 text-xs text-amber-600 dark:text-amber-400">You're running low on storage — consider cleaning up old files or backups.</p>
                @endif
                <a href="{{ route('usage') }}" wire:navigate class="mt-3 inline-block text-sm text-harbor-600 dark:text-harbor-400">View usage breakdown</a>
            </div>

            <div class="card">
                <h3 class="mb-3 font-semibold text-slate-800 dark:text-slate-100">Last backup</h3>
                @if ($lastBackup)
                    <p class="text-sm text-slate-700 dark:text-slate-200">{{ $lastBackup->site->name }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $lastBackup->completed_at->diffForHumans() }} · {{ $lastBackup->sizeHuman() }}</p>
                @else
                    <p class="text-sm text-slate-500 dark:text-slate-400">No backups yet.</p>
                @endif
            </div>

            <div class="card">
                <h3 class="mb-3 font-semibold text-slate-800 dark:text-slate-100">Recent activity</h3>
                <ul class="space-y-2 text-sm">
                    @forelse ($recentActivity as $log)
                        <li class="text-slate-600 dark:text-slate-300">
                            {{ $log->description ?? $log->action }}
                            <span class="block text-xs text-slate-400">{{ $log->created_at->diffForHumans() }}</span>
                        </li>
                    @empty
                        <li class="text-slate-400">No activity yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
