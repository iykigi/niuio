<div>
    @if ($open)
        <div class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-24" x-data @keydown.escape.window="$wire.close()">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative w-full max-w-lg rounded-xl2 bg-white shadow-lift dark:bg-surface-900" x-transition>
                <div class="flex items-center gap-2 border-b border-surface-200 px-4 py-3 dark:border-white/10">
                    <x-heroicon-o-magnifying-glass class="h-5 w-5 text-slate-400" />
                    <input
                        type="text"
                        wire:model.live.debounce.150ms="query"
                        autofocus
                        placeholder="Search websites, domains, databases, actions…"
                        class="w-full border-0 bg-transparent p-0 text-sm text-slate-800 placeholder:text-slate-400 focus:ring-0 dark:text-slate-100"
                    >
                    <kbd class="rounded border border-surface-200 px-1.5 py-0.5 text-xs text-slate-400 dark:border-white/10">ESC</kbd>
                </div>
                <div class="max-h-80 overflow-y-auto p-2">
                    @forelse ($results as $result)
                        <a href="{{ $result['url'] }}" wire:navigate class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm hover:bg-surface-50 dark:hover:bg-white/5">
                            <x-dynamic-component :component="'heroicon-o-'.$result['icon']" class="h-4 w-4 text-slate-400" />
                            <span class="text-slate-700 dark:text-slate-200">{{ $result['label'] }}</span>
                            <span class="ml-auto text-xs text-slate-400">{{ $result['sub'] }}</span>
                        </a>
                    @empty
                        <p class="px-3 py-6 text-center text-sm text-slate-400">No matches.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
