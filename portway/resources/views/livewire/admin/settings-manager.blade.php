<div>
    <h1 class="mb-1 text-xl font-semibold text-slate-900 dark:text-white">Admin panel</h1>
    <p class="mb-6 text-sm text-slate-500 dark:text-slate-400">
        Platform-wide defaults. These override <code class="font-mono text-xs">config/portway.php</code> immediately, with no deploy needed.
        @if ($lastUpdated)
            <span class="text-slate-400">Last changed {{ $lastUpdated->updated_at->diffForHumans() }}.</span>
        @endif
    </p>

    @include('admin.partials.nav')

    <div class="space-y-6">
        <div class="card max-w-2xl">
            <h3 class="mb-4 font-semibold text-slate-800 dark:text-slate-100">Default quotas for new accounts</h3>
            <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Everyone still gets free hosting — this only sets the ceiling new signups start with. Existing accounts keep whatever quotas they already have unless changed individually.</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label">Storage quota (MB)</label>
                    <input type="number" wire:model="defaultStorageQuotaMb" class="input">
                </div>
                <div>
                    <label class="label">Max websites</label>
                    <input type="number" wire:model="defaultMaxWebsites" class="input">
                </div>
                <div>
                    <label class="label">Max databases</label>
                    <input type="number" wire:model="defaultMaxDatabases" class="input">
                </div>
            </div>
            <label class="mt-4 flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                <input type="checkbox" wire:model="backupsCountTowardQuota" class="rounded border-surface-300 text-harbor-600">
                Count backup storage toward each account's storage quota
            </label>
            <button wire:click="saveQuotas" class="btn-primary mt-5">Save quotas</button>
        </div>

        <div class="card max-w-2xl">
            <h3 class="mb-4 font-semibold text-slate-800 dark:text-slate-100">Available runtimes</h3>
            <div class="space-y-4">
                <div>
                    <label class="label">PHP versions (comma-separated)</label>
                    <input type="text" wire:model="availablePhpVersions" class="input font-mono text-sm">
                </div>
                <div>
                    <label class="label">Node.js versions (comma-separated)</label>
                    <input type="text" wire:model="availableNodeVersions" class="input font-mono text-sm">
                </div>
            </div>
            <button wire:click="saveRuntimes" class="btn-primary mt-5">Save runtimes</button>
        </div>

        <div class="card max-w-2xl">
            <h3 class="mb-4 font-semibold text-slate-800 dark:text-slate-100">Feature flags</h3>
            <div class="space-y-3">
                <label class="flex items-center justify-between">
                    <span class="text-sm text-slate-700 dark:text-slate-200">Open registration</span>
                    <input type="checkbox" wire:model="registrationOpen" class="rounded border-surface-300 text-harbor-600">
                </label>
                <label class="flex items-center justify-between">
                    <span class="text-sm text-slate-700 dark:text-slate-200">Email hosting module</span>
                    <input type="checkbox" wire:model="emailHostingEnabled" class="rounded border-surface-300 text-harbor-600">
                </label>
                <label class="flex items-center justify-between">
                    <span class="text-sm text-slate-700 dark:text-slate-200">Malware / file-integrity scanning</span>
                    <input type="checkbox" wire:model="malwareScanningEnabled" class="rounded border-surface-300 text-harbor-600">
                </label>
            </div>
            <button wire:click="saveFeatures" class="btn-primary mt-5">Save feature flags</button>
        </div>

        <div class="card max-w-2xl">
            <h3 class="mb-4 font-semibold text-slate-800 dark:text-slate-100">Network</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label">Public server IP</label>
                    <input type="text" wire:model="serverIp" class="input font-mono text-sm">
                    @error('serverIp') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-slate-400">Shown to users as the A record target when connecting a domain.</p>
                </div>
                <div>
                    <label class="label">Temporary domain suffix</label>
                    <input type="text" wire:model="temporaryDomainSuffix" class="input font-mono text-sm">
                    @error('temporaryDomainSuffix') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <button wire:click="saveNetwork" class="btn-primary mt-5">Save network settings</button>
        </div>
    </div>
</div>
