<div class="space-y-6">
    <div>
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Usage</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Everything on Portway is free — these limits exist purely to keep the platform fair for everyone.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Websites" :value="$websitesUsed" icon="globe-alt" />
        <x-stat-card label="Databases" :value="$databasesUsed" icon="circle-stack" />
        <x-stat-card label="Domains" :value="$domainsUsed" icon="link" />
    </div>

    <div class="card">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100">Storage</h3>
            <span class="text-sm text-slate-500 dark:text-slate-400">{{ $storageUsedHuman }} of {{ $storageQuotaHuman }} used</span>
        </div>
        <x-progress-bar :percent="$storageUsedPercent" />

        <div class="mt-5 grid grid-cols-3 gap-4 text-sm">
            <div>
                <p class="text-slate-400">Websites</p>
                <p class="font-medium text-slate-700 dark:text-slate-200">{{ number_format($websitesBytes / 1048576, 1) }} MB</p>
            </div>
            <div>
                <p class="text-slate-400">Databases</p>
                <p class="font-medium text-slate-700 dark:text-slate-200">{{ number_format($databasesBytes / 1048576, 1) }} MB</p>
            </div>
            @if ($backupsBytes > 0)
                <div>
                    <p class="text-slate-400">Backups</p>
                    <p class="font-medium text-slate-700 dark:text-slate-200">{{ number_format($backupsBytes / 1048576, 1) }} MB</p>
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100">Bandwidth this month</h3>
            <span class="text-sm text-slate-500 dark:text-slate-400">{{ number_format($bandwidthUsedBytes / 1048576, 1) }} MB of {{ number_format($bandwidthQuotaBytes / 1048576, 0) }} MB</span>
        </div>
        <x-progress-bar :percent="$bandwidthUsedPercent" />
    </div>

    <div class="card">
        <h3 class="mb-4 font-semibold text-slate-800 dark:text-slate-100">Bandwidth — last 14 days</h3>
        <div class="flex items-end gap-2" style="height: 140px;">
            @foreach ($chart as $day)
                <div class="flex flex-1 flex-col items-center gap-1">
                    <div
                        class="w-full rounded-t bg-harbor-500/80 dark:bg-harbor-400/70"
                        style="height: {{ max(2, round(($day['bandwidth'] / $maxBandwidth) * 110)) }}px"
                        title="{{ $day['label'] }}: {{ number_format($day['bandwidth'] / 1048576, 1) }} MB"
                    ></div>
                    <span class="text-[10px] text-slate-400">{{ substr($day['label'], 0, 6) }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card">
        <h3 class="mb-3 font-semibold text-slate-800 dark:text-slate-100">Storage by website</h3>

        @if ($sitesByUsage->isEmpty())
            <x-empty-state icon="globe-alt" title="No websites yet" />
        @else
            <ul class="divide-y divide-surface-100 dark:divide-white/5">
                @foreach ($sitesByUsage as $site)
                    <li class="flex items-center justify-between py-3 text-sm">
                        <a href="{{ route('sites.show', $site) }}" wire:navigate class="font-medium text-slate-700 hover:text-harbor-700 dark:text-slate-200">{{ $site->name }}</a>
                        <span class="text-slate-400">{{ $site->diskUsageHuman() }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
