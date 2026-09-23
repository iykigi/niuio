<div class="space-y-6">
    <div class="card">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100">Cron jobs</h3>
            <button wire:click="openCreate" class="btn-primary !py-1.5 text-sm">Add cron job</button>
        </div>

        @if ($cronJobs->isEmpty())
            <x-empty-state icon="clock" title="No cron jobs yet" description="Schedule recurring commands, such as artisan tasks or WordPress cron, to run automatically." />
        @else
            <ul class="divide-y divide-surface-100 dark:divide-white/5">
                @foreach ($cronJobs as $cronJob)
                    <li class="flex items-center justify-between py-3 text-sm">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-slate-800 dark:text-slate-100">{{ $cronJob->label ?: 'Untitled job' }}</span>
                                <span class="badge-neutral font-mono">{{ $cronJob->schedule }}</span>
                                @if (! $cronJob->is_active)
                                    <span class="badge-neutral">Paused</span>
                                @endif
                            </div>
                            <p class="truncate font-mono text-xs text-slate-500 dark:text-slate-400">{{ $cronJob->command }}</p>
                            @if ($cronJob->last_run_at)
                                <p class="mt-0.5 text-xs text-slate-400">
                                    Last run {{ $cronJob->last_run_at->diffForHumans() }}
                                    &middot; exit code {{ $cronJob->last_exit_code ?? '—' }}
                                </p>
                            @endif
                        </div>
                        <div class="flex items-center gap-1">
                            <button wire:click="toggleActive({{ $cronJob->id }})" class="btn-ghost !py-1 !px-2 text-xs">
                                {{ $cronJob->is_active ? 'Pause' : 'Resume' }}
                            </button>
                            <button wire:click="openEdit({{ $cronJob->id }})" class="btn-ghost !py-1 !px-2 text-xs">Edit</button>
                            <button wire:click="confirmDelete({{ $cronJob->id }})" class="btn-ghost !py-1 !px-2 text-xs text-rose-600">Delete</button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <x-modal :show="$showForm" wire:click.outside="$set('showForm', false)">
        <h3 class="mb-4 font-semibold text-slate-800 dark:text-slate-100">{{ $editingId ? 'Edit cron job' : 'New cron job' }}</h3>

        <div class="space-y-4">
            <div>
                <label class="label">Label</label>
                <input type="text" wire:model="label" placeholder="Clear expired sessions" class="input">
            </div>
            <div>
                <label class="label">Command</label>
                <input type="text" wire:model="command" placeholder="php artisan schedule:run" class="input font-mono text-sm">
                @error('command') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Schedule</label>
                <select wire:model.live="preset" class="input">
                    <option value="every_minute">Every minute</option>
                    <option value="every_5_minutes">Every 5 minutes</option>
                    <option value="every_15_minutes">Every 15 minutes</option>
                    <option value="hourly">Hourly</option>
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="custom">Custom cron expression</option>
                </select>
            </div>
            @if ($preset === 'custom')
                <div>
                    <label class="label">Cron expression</label>
                    <input type="text" wire:model="customSchedule" placeholder="*/10 * * * *" class="input font-mono text-sm">
                    @error('customSchedule') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
            @endif
        </div>

        <div class="mt-5 flex justify-end gap-2">
            <button wire:click="$set('showForm', false)" class="btn-secondary">Cancel</button>
            <button wire:click="save" class="btn-primary">{{ $editingId ? 'Save changes' : 'Create cron job' }}</button>
        </div>
    </x-modal>

    <x-modal :show="(bool) $deleteTargetId" wire:click.outside="$set('deleteTargetId', null)">
        <h3 class="mb-2 font-semibold text-slate-800 dark:text-slate-100">Delete this cron job?</h3>
        <p class="mb-5 text-sm text-slate-500 dark:text-slate-400">It will stop running immediately.</p>
        <div class="flex justify-end gap-2">
            <button wire:click="$set('deleteTargetId', null)" class="btn-secondary">Cancel</button>
            <button wire:click="delete" class="btn-danger">Delete</button>
        </div>
    </x-modal>
</div>
