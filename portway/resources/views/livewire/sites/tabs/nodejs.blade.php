<div class="card max-w-lg">
    <h3 class="mb-4 font-semibold text-slate-800 dark:text-slate-100">Node.js settings</h3>

    <div class="space-y-4">
        <div>
            <label class="label">Node.js version</label>
            <select wire:model="site.node_version" class="input">
                @foreach (config('portway.node_versions') as $version)
                    <option value="{{ $version }}">Node {{ $version }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Install command</label>
            <input type="text" wire:model="site.node_install_command" class="input font-mono text-sm">
        </div>
        <div>
            <label class="label">Build command</label>
            <input type="text" wire:model="site.node_build_command" class="input font-mono text-sm">
        </div>
        <div>
            <label class="label">Start command</label>
            <input type="text" wire:model="site.node_start_command" class="input font-mono text-sm">
        </div>
    </div>

    <div class="mt-5 flex gap-2">
        <button wire:click="saveNodeSettings" class="btn-primary">Save settings</button>
        <button wire:click="restartNode" class="btn-secondary">Restart process</button>
    </div>

    <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">
        Process status: <span class="badge-neutral">{{ $site->node_process_status ?? 'stopped' }}</span>
    </p>
</div>
