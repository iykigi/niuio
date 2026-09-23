<div class="space-y-6">
    <div class="card">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">Create a backup</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $usedBackups }} of {{ $maxBackups }} backups used for this website.</p>
            </div>
            <div class="flex items-end gap-2">
                <select wire:model="type" class="input !py-2">
                    <option value="full">Full (files + database)</option>
                    <option value="files">Files only</option>
                    <option value="database">Database only</option>
                </select>
                <button wire:click="create" wire:loading.attr="disabled" class="btn-primary">Create backup</button>
            </div>
        </div>
    </div>

    <div class="card">
        <h3 class="mb-3 font-semibold text-slate-800 dark:text-slate-100">Backup history</h3>

        @if ($backups->isEmpty())
            <x-empty-state icon="archive-box" title="No backups yet" description="Create your first backup above — it will run in the background." />
        @else
            <ul class="divide-y divide-surface-100 dark:divide-white/5">
                @foreach ($backups as $backup)
                    <li class="flex items-center justify-between py-3 text-sm">
                        <div>
                            <span class="{{ $backup->status->badgeClass() }}">{{ $backup->status->label() }}</span>
                            <span class="ml-2 font-medium capitalize text-slate-700 dark:text-slate-200">{{ $backup->type }}</span>
                            <span class="ml-2 text-slate-500 dark:text-slate-400">{{ $backup->created_at->diffForHumans() }}</span>
                            <span class="ml-2 text-slate-400">{{ $backup->sizeHuman() }}</span>
                            @if ($backup->status === $statuses::Failed && $backup->failure_reason)
                                <p class="mt-1 text-xs text-rose-600">{{ $backup->failure_reason }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-1">
                            @if ($backup->status === $statuses::Completed)
                                <button wire:click="confirmRestore({{ $backup->id }})" class="btn-ghost !py-1 !px-2 text-xs">Restore</button>
                            @endif
                            <button wire:click="confirmDelete({{ $backup->id }})" class="btn-ghost !py-1 !px-2 text-xs text-rose-600">Delete</button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <x-modal :show="(bool) $restoreTargetId" wire:click.outside="$set('restoreTargetId', null)">
        <h3 class="mb-2 font-semibold text-slate-800 dark:text-slate-100">Restore this backup?</h3>
        <p class="mb-5 text-sm text-slate-500 dark:text-slate-400">
            This overwrites the website's current files and database with the contents of the backup. This cannot be undone.
        </p>
        <div class="flex justify-end gap-2">
            <button wire:click="$set('restoreTargetId', null)" class="btn-secondary">Cancel</button>
            <button wire:click="restore" class="btn-danger">Restore</button>
        </div>
    </x-modal>

    <x-modal :show="(bool) $deleteTargetId" wire:click.outside="$set('deleteTargetId', null)">
        <h3 class="mb-2 font-semibold text-slate-800 dark:text-slate-100">Delete this backup?</h3>
        <p class="mb-5 text-sm text-slate-500 dark:text-slate-400">The backup archive will be permanently removed.</p>
        <div class="flex justify-end gap-2">
            <button wire:click="$set('deleteTargetId', null)" class="btn-secondary">Cancel</button>
            <button wire:click="delete" class="btn-danger">Delete</button>
        </div>
    </x-modal>
</div>
