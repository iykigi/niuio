@props(['label', 'value', 'icon' => null, 'trend' => null, 'trendUp' => true])

<div class="card">
    <div class="flex items-start justify-between">
        <div>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ $label }}</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white">{{ $value }}</p>
        </div>
        @if ($icon)
            <span class="rounded-lg bg-harbor-50 p-2 text-harbor-600 dark:bg-harbor-500/10 dark:text-harbor-300">
                <x-dynamic-component :component="'heroicon-o-'.$icon" class="h-5 w-5" />
            </span>
        @endif
    </div>
    @if ($trend)
        <p class="mt-2 text-xs {{ $trendUp ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
            {{ $trend }}
        </p>
    @endif
</div>
