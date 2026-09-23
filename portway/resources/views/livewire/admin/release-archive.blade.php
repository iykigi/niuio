<div>
    @include('admin.partials.nav')

    <div class="mb-6">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Release archive</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Builds their owners have deleted. Nothing here is visible to the owner any more — restore a build to hand it back, or purge it to destroy the file for good.
        </p>
    </div>

    <div class="mb-4 flex flex-wrap gap-3">
        <input type="search" wire:model.live.debounce.300ms="search" class="input max-w-xs" placeholder="Search by app, version or owner email…">
        <select wire:model.live="platform" class="input max-w-[12rem]">
            <option value="">All platforms</option>
            @foreach ($platforms as $platformOption)
                <option value="{{ $platformOption->value }}">{{ $platformOption->label() }}</option>
            @endforeach
        </select>
    </div>

    @if ($releases->isEmpty())
        <x-empty-state
            icon="archive-box"
            title="The archive is empty"
            description="When a user deletes a build it lands here instead of being destroyed, so it can always be recovered."
        />
    @else
        <div class="card overflow-hidden !p-0">
            <table class="w-full text-sm">
                <thead class="border-b border-surface-200 bg-surface-50 text-left text-xs uppercase tracking-wide text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Application</th>
                        <th class="px-4 py-3">Version</th>
                        <th class="px-4 py-3">Platform</th>
                        <th class="px-4 py-3">Owner</th>
                        <th class="px-4 py-3">Deleted</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-100 dark:divide-white/5">
                    @foreach ($releases as $release)
                        <tr wire:key="trashed-{{ $release->id }}">
                            <td class="px-4 py-3 text-slate-800 dark:text-slate-100">{{ $release->application?->name ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-800 dark:text-slate-100">{{ $release->version }}</td>
                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $release->platform->label() }}</td>
                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $release->user?->email ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">
                                {{ $release->trashed_at?->diffForHumans() ?? '—' }}
                                @if ($release->trashedBy)
                                    <span class="block text-xs text-slate-400">by {{ $release->trashedBy->name }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    @can('download', $release)
                                        <a href="{{ route('releases.download', $release) }}" class="btn-ghost !p-1.5" title="Download this build">
                                            <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                                        </a>
                                    @endcan
                                    @can('restore', $release)
                                        <button wire:click="confirmRestore({{ $release->id }})" class="btn-secondary !px-3 !py-1.5 text-xs">Restore to owner</button>
                                    @endcan
                                    @can('forceDelete', $release)
                                        <button wire:click="confirmPurge({{ $release->id }})" class="btn-ghost !p-1.5 text-rose-600" title="Delete permanently">
                                            <x-heroicon-o-trash class="h-4 w-4" />
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $releases->links() }}</div>
    @endif

    <x-modal :show="(bool) $restoreTargetId" wire:click.outside="$set('restoreTargetId', null)">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Restore this build to its owner?</h3>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
            It reappears in the owner's account as an archived build. It does not go live on its own — the owner decides whether to publish it again.
        </p>
        <div class="mt-6 flex justify-end gap-2">
            <button wire:click="$set('restoreTargetId', null)" class="btn-secondary">Cancel</button>
            <button wire:click="restore" wire:loading.attr="disabled" class="btn-primary">Restore</button>
        </div>
    </x-modal>

    <x-modal :show="(bool) $purgeTargetId" wire:click.outside="$set('purgeTargetId', null)">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Permanently delete this build?</h3>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
            The installer file and its record are destroyed. This cannot be undone, and the owner cannot get it back. The activity log keeps a record that it happened.
        </p>
        <div class="mt-6 flex justify-end gap-2">
            <button wire:click="$set('purgeTargetId', null)" class="btn-secondary">Cancel</button>
            <button wire:click="purge" wire:loading.attr="disabled" class="btn-danger">Delete permanently</button>
        </div>
    </x-modal>
</div>
