<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <x-stat-card label="Disk usage" :value="$site->diskUsageHuman()" icon="server" />
    <x-stat-card label="PHP version" :value="$site->php_version ?? '—'" icon="code-bracket" />
    <x-stat-card label="Runtime" :value="ucfirst($site->runtime)" icon="cpu-chip" />
    <x-stat-card label="Last deployed" :value="$site->last_deployed_at?->diffForHumans() ?? 'Never'" icon="rocket-launch" />
</div>

<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
    <div class="card">
        <h3 class="mb-3 font-semibold text-slate-800 dark:text-slate-100">Domains</h3>
        <ul class="space-y-2 text-sm">
            @forelse ($site->domains as $domain)
                <li class="flex items-center justify-between">
                    <span class="text-slate-700 dark:text-slate-200">{{ $domain->hostname }}</span>
                    <span class="{{ $domain->status->badgeClass() }}">{{ $domain->status->label() }}</span>
                </li>
            @empty
                <li class="text-slate-400">No domains connected.</li>
            @endforelse
        </ul>
    </div>

    <div class="card">
        <h3 class="mb-3 font-semibold text-slate-800 dark:text-slate-100">Databases</h3>
        <ul class="space-y-2 text-sm">
            @forelse ($site->databases as $database)
                <li class="flex items-center justify-between">
                    <span class="text-slate-700 dark:text-slate-200">{{ $database->name }}</span>
                    <span class="text-slate-400">{{ $database->sizeHuman() }}</span>
                </li>
            @empty
                <li class="text-slate-400">No databases yet.</li>
            @endforelse
        </ul>
    </div>
</div>
