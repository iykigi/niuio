<div>
    <h1 class="mb-1 text-xl font-semibold text-slate-900 dark:text-white">Admin panel</h1>
    <p class="mb-6 text-sm text-slate-500 dark:text-slate-400">Platform-wide banners shown to users on their dashboard.</p>

    @include('admin.partials.nav')

    <div class="mb-4 flex justify-end">
        <button wire:click="openCreate" class="btn-primary">New announcement</button>
    </div>

    @if ($announcements->isEmpty())
        <x-empty-state icon="megaphone" title="No announcements yet" />
    @else
        <div class="card">
            <ul class="divide-y divide-surface-100 dark:divide-white/5">
                @foreach ($announcements as $announcement)
                    <li class="flex items-center justify-between py-3 text-sm">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="{{ match ($announcement->severity) {
                                    'critical' => 'badge-danger',
                                    'warning' => 'badge-warning',
                                    default => 'badge-info',
                                } }} capitalize">{{ $announcement->severity }}</span>
                                <span class="badge-neutral capitalize">{{ $announcement->type }}</span>
                                <span class="font-medium text-slate-800 dark:text-slate-100">{{ $announcement->title }}</span>
                                @unless ($announcement->is_published)
                                    <span class="badge-neutral">Draft</span>
                                @endunless
                            </div>
                            <p class="mt-0.5 truncate text-xs text-slate-400">{{ $announcement->body }}</p>
                        </div>
                        <div class="flex items-center gap-1">
                            <button wire:click="togglePublished({{ $announcement->id }})" class="btn-ghost !py-1 !px-2 text-xs">
                                {{ $announcement->is_published ? 'Unpublish' : 'Publish' }}
                            </button>
                            <button wire:click="openEdit({{ $announcement->id }})" class="btn-ghost !py-1 !px-2 text-xs">Edit</button>
                            <button wire:click="confirmDelete({{ $announcement->id }})" class="btn-ghost !py-1 !px-2 text-xs text-rose-600">Delete</button>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-modal :show="$showForm" max-width="lg" wire:click.outside="$set('showForm', false)">
        <h3 class="mb-4 font-semibold text-slate-800 dark:text-slate-100">{{ $editingId ? 'Edit announcement' : 'New announcement' }}</h3>
        <div class="space-y-4">
            <div>
                <label class="label">Title</label>
                <input type="text" wire:model="title" class="input">
                @error('title') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Message</label>
                <textarea wire:model="body" rows="3" class="input"></textarea>
                @error('body') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label">Type</label>
                    <select wire:model="type" class="input">
                        <option value="general">General</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="feature">New feature</option>
                        <option value="security">Security</option>
                    </select>
                </div>
                <div>
                    <label class="label">Severity</label>
                    <select wire:model="severity" class="input">
                        <option value="info">Info</option>
                        <option value="warning">Warning</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <div>
                    <label class="label">Starts at (optional)</label>
                    <input type="datetime-local" wire:model="startsAt" class="input">
                </div>
                <div>
                    <label class="label">Ends at (optional)</label>
                    <input type="datetime-local" wire:model="endsAt" class="input">
                    @error('endsAt') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                <input type="checkbox" wire:model="isPublished" class="rounded border-surface-300 text-harbor-600">
                Published (visible to users immediately, subject to the dates above)
            </label>
        </div>
        <div class="mt-5 flex justify-end gap-2">
            <button wire:click="$set('showForm', false)" class="btn-secondary">Cancel</button>
            <button wire:click="save" class="btn-primary">{{ $editingId ? 'Save changes' : 'Publish' }}</button>
        </div>
    </x-modal>

    <x-modal :show="(bool) $deleteTargetId" wire:click.outside="$set('deleteTargetId', null)">
        <h3 class="mb-2 font-semibold text-slate-800 dark:text-slate-100">Delete this announcement?</h3>
        <div class="flex justify-end gap-2">
            <button wire:click="$set('deleteTargetId', null)" class="btn-secondary">Cancel</button>
            <button wire:click="delete" class="btn-danger">Delete</button>
        </div>
    </x-modal>
</div>
