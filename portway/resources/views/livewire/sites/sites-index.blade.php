<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="relative w-full max-w-xs">
            <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search websites…" class="input pl-9">
        </div>
        <a href="{{ route('sites.create') }}" wire:navigate class="btn-primary">
            <x-heroicon-o-plus class="h-4 w-4" /> Create Website
        </a>
    </div>

    @if ($sites->isEmpty())
        <x-empty-state icon="globe-alt" title="No websites yet" description="Create your first website — PHP, Laravel, WordPress, Node.js and more.">
            <x-slot:action>
                <a href="{{ route('sites.create') }}" wire:navigate class="btn-primary">Create Website</a>
            </x-slot:action>
        </x-empty-state>
    @else
        <div class="card overflow-hidden !p-0">
            <table class="w-full text-sm">
                <thead class="border-b border-surface-200 bg-surface-50 text-left text-xs uppercase tracking-wide text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Website</th>
                        <th class="hidden px-4 py-3 sm:table-cell">Domain</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="hidden px-4 py-3 md:table-cell">Storage</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-100 dark:divide-white/5">
                    @foreach ($sites as $site)
                        <tr wire:key="site-{{ $site->id }}">
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-800 dark:text-slate-100">{{ $site->name }}</p>
                                <p class="text-xs text-slate-400">{{ ucfirst(str_replace('_', ' ', $site->project_type)) }}</p>
                            </td>
                            <td class="hidden px-4 py-3 text-slate-500 dark:text-slate-400 sm:table-cell">
                                {{ $site->domains->firstWhere('type', 'primary')?->hostname ?? $site->temporaryHostname() }}
                            </td>
                            <td class="px-4 py-3"><span class="{{ $site->status->badgeClass() }}">{{ $site->status->label() }}</span></td>
                            <td class="hidden px-4 py-3 text-slate-500 dark:text-slate-400 md:table-cell">{{ $site->diskUsageHuman() }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('sites.show', ['site' => $site, 'tab' => $toolTab]) }}" wire:navigate class="btn-secondary !px-3 !py-1.5 text-xs">
                                    {{ $toolLabel }}
                                </a>
                                <button wire:click="confirmDelete({{ $site->id }})" class="btn-ghost !px-2 !py-1.5 text-xs text-rose-600 dark:text-rose-400">
                                    <x-heroicon-o-trash class="h-4 w-4" />
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $sites->links() }}</div>
    @endif

    <x-modal :show="$confirmingDeleteId !== null" wire:click.outside="$set('confirmingDeleteId', null)">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Delete this website?</h3>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            This permanently deletes its files, databases and domains. This cannot be undone.
        </p>
        <div class="mt-5 flex justify-end gap-2">
            <button wire:click="$set('confirmingDeleteId', null)" class="btn-secondary">Cancel</button>
            <button wire:click="delete" class="btn-danger">Delete website</button>
        </div>
    </x-modal>
</div>
