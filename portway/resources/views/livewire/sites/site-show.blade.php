<div>
    <div class="mb-4 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">{{ $site->name }}</h2>
                <span class="{{ $site->status->badgeClass() }}">{{ $site->status->label() }}</span>
            </div>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ $site->domains->firstWhere('type', 'primary')?->hostname ?? $site->temporaryHostname() }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="https://{{ $site->domains->firstWhere('type', 'primary')?->hostname ?? $site->temporaryHostname() }}" target="_blank" class="btn-secondary">
                <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4" /> Visit Site
            </a>
            <button wire:click="setTab('files')" class="btn-secondary"><x-heroicon-o-folder class="h-4 w-4" /> File Manager</button>
            <button wire:click="setTab('terminal')" class="btn-secondary"><x-heroicon-o-command-line class="h-4 w-4" /> Terminal</button>
            @if ($site->gitRepository)
                <button wire:click="setTab('git')" class="btn-primary"><x-heroicon-o-rocket-launch class="h-4 w-4" /> Deploy</button>
            @endif
        </div>
    </div>

    <div class="mb-6 overflow-x-auto">
        <div class="flex gap-1 border-b border-surface-200 dark:border-white/10">
            @foreach ($tabs as $key => $label)
                <button
                    wire:click="setTab(@js($key))"
                    class="whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium {{ $tab === $key ? 'border-harbor-600 text-harbor-700 dark:border-harbor-400 dark:text-harbor-300' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <div>
        @includeIf('livewire.sites.tabs.'.$tab, ['site' => $site])
    </div>
</div>
