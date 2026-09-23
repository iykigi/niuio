<div class="space-y-6">
    <div class="card">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">File integrity</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    @if ($flaggedCount > 0)
                        <span class="text-rose-600">{{ $flaggedCount }} flagged change{{ $flaggedCount === 1 ? '' : 's' }}</span> detected in this website's files.
                    @else
                        No suspicious file changes flagged.
                    @endif
                </p>
            </div>
            <div class="flex gap-1 rounded-lg bg-surface-100 p-1 text-sm dark:bg-white/5">
                <button wire:click="$set('filter', 'flagged')" class="rounded-md px-3 py-1 {{ $filter === 'flagged' ? 'bg-white shadow-sm dark:bg-surface-800' : '' }}">Flagged</button>
                <button wire:click="$set('filter', 'all')" class="rounded-md px-3 py-1 {{ $filter === 'all' ? 'bg-white shadow-sm dark:bg-surface-800' : '' }}">All changes</button>
            </div>
        </div>

        @if ($changes->isEmpty())
            <x-empty-state icon="shield-check" title="Nothing to show" description="File changes on this website will appear here as they're detected." />
        @else
            <ul class="divide-y divide-surface-100 dark:divide-white/5">
                @foreach ($changes as $event)
                    <li class="flex items-center justify-between py-3 text-sm">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                @if ($event->flagged)
                                    <span class="badge-danger">Flagged</span>
                                @endif
                                <span class="badge-neutral capitalize">{{ $event->event }}</span>
                                <span class="truncate font-mono text-slate-700 dark:text-slate-200">{{ $event->path }}</span>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-400">{{ $event->detected_at?->diffForHumans() }}</p>
                        </div>
                        @if ($event->flagged)
                            <button wire:click="markReviewed({{ $event->id }})" class="btn-ghost !py-1 !px-2 text-xs">Mark reviewed</button>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="card">
        <h3 class="mb-3 font-semibold text-slate-800 dark:text-slate-100">Recent activity</h3>

        @if ($activity->isEmpty())
            <x-empty-state icon="clock" title="No activity recorded yet" />
        @else
            <ul class="divide-y divide-surface-100 dark:divide-white/5">
                @foreach ($activity as $entry)
                    <li class="py-3 text-sm">
                        <span class="font-medium text-slate-700 dark:text-slate-200">{{ $entry->description ?: $entry->action }}</span>
                        <span class="ml-2 text-slate-400">{{ $entry->created_at->diffForHumans() }}</span>
                        @if ($entry->ip_address)
                            <span class="ml-2 font-mono text-xs text-slate-400">{{ $entry->ip_address }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
