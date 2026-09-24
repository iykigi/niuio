<div class="card max-w-lg">
    <h3 class="mb-4 font-semibold text-slate-800 dark:text-slate-100">PHP settings</h3>

    <div class="space-y-4">
        <div>
            <label class="label">PHP version</label>
            <select wire:model="phpVersion" class="input">
                @foreach (config('portway.php_versions') as $version)
                    <option value="{{ $version }}">PHP {{ $version }}</option>
                @endforeach
            </select>
            @error('phpVersion') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label">Memory limit (MB)</label>
            <input type="number" wire:model="phpMemoryLimitMb" class="input">
            @error('phpMemoryLimitMb') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label">Upload max filesize (MB)</label>
            <input type="number" wire:model="phpUploadMaxMb" class="input">
            @error('phpUploadMaxMb') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label">Max execution time (seconds)</label>
            <input type="number" wire:model="phpMaxExecutionSeconds" class="input">
            @error('phpMaxExecutionSeconds') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <button wire:click="savePhpSettings" class="btn-primary mt-5">Save PHP settings</button>
</div>
