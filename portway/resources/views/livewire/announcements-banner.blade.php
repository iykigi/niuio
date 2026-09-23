<div>
    @if ($announcement)
        @php
            $styles = [
                'info' => 'border-harbor-200 bg-harbor-50 text-harbor-800 dark:border-harbor-500/20 dark:bg-harbor-500/10 dark:text-harbor-200',
                'warning' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-200',
                'critical' => 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-200',
            ];
        @endphp
        <div class="mb-6 flex items-start gap-3 rounded-xl2 border p-4 {{ $styles[$announcement->severity] ?? $styles['info'] }}">
            <x-heroicon-o-megaphone class="mt-0.5 h-5 w-5 flex-shrink-0" />
            <div class="flex-1">
                <p class="font-medium">{{ $announcement->title }}</p>
                <p class="mt-0.5 text-sm opacity-90">{{ $announcement->body }}</p>
            </div>
            <button wire:click="dismiss" class="opacity-60 hover:opacity-100">
                <x-heroicon-o-x-mark class="h-4 w-4" />
            </button>
        </div>
    @endif
</div>
