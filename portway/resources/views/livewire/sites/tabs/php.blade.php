<div class="card max-w-lg">
    <h3 class="mb-4 font-semibold text-slate-800 dark:text-slate-100">PHP settings</h3>

    <div class="space-y-4">
        <div>
            <label class="label">PHP version</label>
            <select wire:model="site.php_version" class="input">
                @foreach (config('portway.php_versions') as $version)
                    <option value="{{ $version }}">PHP {{ $version }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Memory limit (MB)</label>
            <input type="number" wire:model="site.php_memory_limit_mb" class="input">
        </div>
        <div>
            <label class="label">Upload max filesize (MB)</label>
            <input type="number" wire:model="site.php_upload_max_mb" class="input">
        </div>
        <div>
            <label class="label">Max execution time (seconds)</label>
            <input type="number" wire:model="site.php_max_execution_seconds" class="input">
        </div>
    </div>

    <button wire:click="savePhpSettings" class="btn-primary mt-5">Save PHP settings</button>
</div>
