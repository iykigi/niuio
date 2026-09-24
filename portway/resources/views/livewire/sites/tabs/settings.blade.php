<div class="card max-w-lg">
    <h3 class="mb-4 font-semibold text-slate-800 dark:text-slate-100">General settings</h3>

    <div class="space-y-4">
        <div>
            <label class="label">Website name</label>
            <input type="text" wire:model="name" class="input">
            @error('name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label">Document root</label>
            <input type="text" wire:model="documentRoot" class="input font-mono text-sm">
            @error('documentRoot') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <button wire:click="saveGeneralSettings" class="btn-primary mt-5">Save</button>

    <div class="mt-6 border-t border-surface-200 pt-4 dark:border-white/10">
        <label class="flex items-center justify-between">
            <span class="text-sm font-medium text-slate-800 dark:text-slate-100">Force HTTPS</span>
            <input type="checkbox" wire:click="toggleForceHttps" @checked($site->force_https) class="rounded border-surface-300 text-harbor-600">
        </label>
    </div>
</div>
