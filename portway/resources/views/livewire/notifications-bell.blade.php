<div x-data="{ open: @entangle('open') }" @click.outside="open = false" class="relative">
    <button type="button" @click="open = !open" class="btn-ghost relative !px-2">
        <x-heroicon-o-bell class="h-5 w-5" />
        @if ($unreadCount > 0)
            <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-beacon-500 px-1 text-[10px] font-semibold text-white">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="open" x-cloak class="absolute right-0 z-20 mt-2 w-80 rounded-xl2 border border-surface-200 bg-white shadow-lift dark:border-white/10 dark:bg-surface-900">
        <div class="flex items-center justify-between border-b border-surface-200 px-4 py-3 dark:border-white/10">
            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">Notifications</p>
            @if ($unreadCount > 0)
                <button wire:click="markAllRead" class="text-xs font-medium text-harbor-600 dark:text-harbor-400">Mark all read</button>
            @endif
        </div>
        <div class="max-h-96 overflow-y-auto">
            @forelse ($notifications as $notification)
                <a
                    href="{{ $notification->data['url'] ?? '#' }}"
                    wire:click="markRead(@js($notification->id))"
                    class="block border-b border-surface-100 px-4 py-3 text-sm hover:bg-surface-50 dark:border-white/5 dark:hover:bg-white/5 {{ $notification->read_at ? 'opacity-60' : '' }}"
                >
                    <p class="font-medium text-slate-800 dark:text-slate-100">{{ $notification->data['title'] ?? 'Update' }}</p>
                    <p class="mt-0.5 text-slate-500 dark:text-slate-400">{{ $notification->data['body'] ?? '' }}</p>
                    <p class="mt-1 text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</p>
                </a>
            @empty
                <p class="px-4 py-8 text-center text-sm text-slate-400">You're all caught up.</p>
            @endforelse
        </div>
    </div>
</div>
