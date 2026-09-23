<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('support.index') }}" wire:navigate class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">&larr; Back to support</a>
            <h1 class="mt-1 text-xl font-semibold text-slate-900 dark:text-white">{{ $ticket->subject }}</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ str($ticket->category)->headline() }} &middot; {{ ucfirst($ticket->priority) }} priority &middot; opened {{ $ticket->created_at->diffForHumans() }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="{{ match ($ticket->status) {
                'open' => 'badge-info',
                'pending' => 'badge-neutral',
                'answered' => 'badge-success',
                'closed' => 'badge-neutral',
            } }} capitalize">{{ $ticket->status }}</span>

            @if ($ticket->status !== 'closed')
                <button wire:click="close" class="btn-secondary !py-1.5 text-sm">Close ticket</button>
            @else
                <button wire:click="reopen" class="btn-secondary !py-1.5 text-sm">Reopen</button>
            @endif
        </div>
    </div>

    <div class="card space-y-5">
        @foreach ($messages as $message)
            <div class="flex gap-3 {{ $message->is_staff_reply ? '' : 'flex-row-reverse text-right' }}">
                <div class="max-w-lg rounded-xl2 p-4 text-sm {{ $message->is_staff_reply ? 'bg-harbor-50 dark:bg-harbor-500/10' : 'bg-surface-100 dark:bg-white/5' }}">
                    <p class="mb-1 text-xs font-medium {{ $message->is_staff_reply ? 'text-harbor-700 dark:text-harbor-300' : 'text-slate-500 dark:text-slate-400' }}">
                        {{ $message->is_staff_reply ? ($message->user->name.' · Portway Support') : $message->user->name }}
                        <span class="ml-1 font-normal text-slate-400">{{ $message->created_at->diffForHumans() }}</span>
                    </p>
                    <p class="whitespace-pre-wrap text-slate-700 dark:text-slate-200">{{ $message->body }}</p>
                </div>
            </div>
        @endforeach
    </div>

    @if ($ticket->status !== 'closed')
        <div class="card">
            <label class="label">Reply</label>
            <textarea wire:model="reply" rows="4" class="input"></textarea>
            @error('reply') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            <button wire:click="sendReply" class="btn-primary mt-3">Send reply</button>
        </div>
    @endif
</div>
