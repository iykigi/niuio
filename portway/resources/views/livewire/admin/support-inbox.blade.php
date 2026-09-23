<div>
    <h1 class="mb-1 text-xl font-semibold text-slate-900 dark:text-white">Admin panel</h1>
    <p class="mb-6 text-sm text-slate-500 dark:text-slate-400">Every support ticket raised across the platform.</p>

    @include('admin.partials.nav')

    <div class="mb-4 flex gap-3">
        <div class="flex gap-1 rounded-lg bg-surface-100 p-1 text-sm dark:bg-white/5">
            <button wire:click="$set('statusFilter', 'open')" class="rounded-md px-3 py-1.5 {{ $statusFilter === 'open' ? 'bg-white shadow-sm dark:bg-surface-800' : '' }}">Open</button>
            <button wire:click="$set('statusFilter', 'closed')" class="rounded-md px-3 py-1.5 {{ $statusFilter === 'closed' ? 'bg-white shadow-sm dark:bg-surface-800' : '' }}">Closed</button>
            <button wire:click="$set('statusFilter', 'all')" class="rounded-md px-3 py-1.5 {{ $statusFilter === 'all' ? 'bg-white shadow-sm dark:bg-surface-800' : '' }}">All</button>
        </div>
        <div class="flex gap-1 rounded-lg bg-surface-100 p-1 text-sm dark:bg-white/5">
            <button wire:click="$set('assignedFilter', '')" class="rounded-md px-3 py-1.5 {{ $assignedFilter === '' ? 'bg-white shadow-sm dark:bg-surface-800' : '' }}">Everyone</button>
            <button wire:click="$set('assignedFilter', 'me')" class="rounded-md px-3 py-1.5 {{ $assignedFilter === 'me' ? 'bg-white shadow-sm dark:bg-surface-800' : '' }}">Assigned to me</button>
            <button wire:click="$set('assignedFilter', 'unassigned')" class="rounded-md px-3 py-1.5 {{ $assignedFilter === 'unassigned' ? 'bg-white shadow-sm dark:bg-surface-800' : '' }}">Unassigned</button>
        </div>
    </div>

    <div class="card">
        @if ($tickets->isEmpty())
            <x-empty-state icon="lifebuoy" title="Nothing here" />
        @else
            <ul class="divide-y divide-surface-100 dark:divide-white/5">
                @foreach ($tickets as $ticket)
                    <li class="flex items-center justify-between py-3 text-sm">
                        <a href="{{ route('support.show', $ticket) }}" wire:navigate class="min-w-0">
                            <p class="font-medium text-slate-800 dark:text-slate-100">{{ $ticket->subject }}</p>
                            <p class="mt-0.5 text-xs text-slate-400">
                                {{ $ticket->user->name }} &middot; {{ str($ticket->category)->headline() }} &middot; {{ $ticket->last_reply_at?->diffForHumans() }}
                            </p>
                        </a>
                        <div class="flex items-center gap-2">
                            <span class="{{ match ($ticket->priority) {
                                'urgent' => 'badge-danger',
                                'high' => 'badge-warning',
                                default => 'badge-neutral',
                            } }} capitalize">{{ $ticket->priority }}</span>
                            <span class="{{ match ($ticket->status) {
                                'open' => 'badge-info',
                                'pending' => 'badge-neutral',
                                'answered' => 'badge-success',
                                'closed' => 'badge-neutral',
                            } }} capitalize">{{ $ticket->status }}</span>
                            @if ($ticket->assignedTo)
                                <span class="text-xs text-slate-400">{{ $ticket->assignedTo->name }}</span>
                            @else
                                <button wire:click="assignToMe({{ $ticket->id }})" class="btn-ghost !py-1 !px-2 text-xs">Assign to me</button>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="mt-4">{{ $tickets->links() }}</div>
    </div>
</div>
