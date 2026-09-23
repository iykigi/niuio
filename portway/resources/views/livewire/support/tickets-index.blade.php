<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Support</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Get help from the Portway team.</p>
        </div>
        <button wire:click="openCreate" class="btn-primary">New ticket</button>
    </div>

    <div class="flex gap-1 rounded-lg bg-surface-100 p-1 text-sm dark:bg-white/5" style="width: fit-content;">
        <button wire:click="$set('filter', 'open')" class="rounded-md px-3 py-1.5 {{ $filter === 'open' ? 'bg-white shadow-sm dark:bg-surface-800' : '' }}">Open</button>
        <button wire:click="$set('filter', 'closed')" class="rounded-md px-3 py-1.5 {{ $filter === 'closed' ? 'bg-white shadow-sm dark:bg-surface-800' : '' }}">Closed</button>
        <button wire:click="$set('filter', 'all')" class="rounded-md px-3 py-1.5 {{ $filter === 'all' ? 'bg-white shadow-sm dark:bg-surface-800' : '' }}">All</button>
    </div>

    <div class="card">
        @if ($tickets->isEmpty())
            <x-empty-state icon="lifebuoy" title="No tickets here" description="Need a hand with something? Open a new support ticket and our team will follow up." />
        @else
            <ul class="divide-y divide-surface-100 dark:divide-white/5">
                @foreach ($tickets as $ticket)
                    <li>
                        <a href="{{ route('support.show', $ticket) }}" wire:navigate class="flex items-center justify-between py-3 text-sm hover:bg-surface-50 dark:hover:bg-white/5">
                            <div>
                                <p class="font-medium text-slate-800 dark:text-slate-100">{{ $ticket->subject }}</p>
                                <p class="mt-0.5 text-xs text-slate-400">
                                    {{ str($ticket->category)->headline() }} &middot; opened {{ $ticket->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <span class="{{ match ($ticket->status) {
                                'open' => 'badge-info',
                                'pending' => 'badge-neutral',
                                'answered' => 'badge-success',
                                'closed' => 'badge-neutral',
                            } }} capitalize">{{ $ticket->status }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <x-modal :show="$showCreate" max-width="lg" wire:click.outside="$set('showCreate', false)">
        <h3 class="mb-4 font-semibold text-slate-800 dark:text-slate-100">New support ticket</h3>
        <div class="space-y-4">
            <div>
                <label class="label">Subject</label>
                <input type="text" wire:model="subject" class="input">
                @error('subject') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label">Category</label>
                    <select wire:model="category" class="input">
                        <option value="technical">Technical</option>
                        <option value="account">Account</option>
                        <option value="domain">Domain</option>
                        <option value="abuse_report">Abuse report</option>
                        <option value="billing_free_tier">Free tier question</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="label">Priority</label>
                    <select wire:model="priority" class="input">
                        <option value="low">Low</option>
                        <option value="normal">Normal</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="label">Related website (optional)</label>
                <select wire:model="siteId" class="input">
                    <option value="">None</option>
                    @foreach ($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Describe the issue</label>
                <textarea wire:model="body" rows="5" class="input"></textarea>
                @error('body') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="mt-5 flex justify-end gap-2">
            <button wire:click="$set('showCreate', false)" class="btn-secondary">Cancel</button>
            <button wire:click="create" class="btn-primary">Submit ticket</button>
        </div>
    </x-modal>
</div>
