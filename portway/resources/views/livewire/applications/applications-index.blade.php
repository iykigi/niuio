<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Distribute desktop builds of your software. One build per platform is live at a time — publishing a new one retires the old one automatically.
        </p>
        <button wire:click="openCreate" class="btn-primary"><x-heroicon-o-plus class="h-4 w-4" /> New application</button>
    </div>

    @if ($applications->total() > 0)
        <div class="mb-4">
            <input type="search" wire:model.live.debounce.300ms="search" class="input max-w-xs" placeholder="Search applications…">
        </div>
    @endif

    @if ($applications->isEmpty())
        <x-empty-state
            icon="cube"
            title="{{ $search !== '' ? 'No applications match that search' : 'No applications yet' }}"
            description="Create an application, upload a build for Windows, macOS or Linux, and Portway gives you a public download page that always serves the current version."
        >
            <x-slot:action>
                <button wire:click="openCreate" class="btn-primary">New application</button>
            </x-slot:action>
        </x-empty-state>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($applications as $application)
                <a
                    href="{{ route('applications.show', $application) }}"
                    wire:navigate
                    wire:key="app-{{ $application->id }}"
                    class="card transition hover:shadow-lift"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-900 dark:text-white">{{ $application->name }}</p>
                            @if ($application->tagline)
                                <p class="mt-0.5 truncate text-sm text-slate-500 dark:text-slate-400">{{ $application->tagline }}</p>
                            @endif
                        </div>
                        <span class="{{ $application->live_releases_count > 0 && $application->is_listed ? 'badge-success' : 'badge-neutral' }} flex-shrink-0">
                            {{ $application->live_releases_count > 0 && $application->is_listed ? 'Published' : 'Unpublished' }}
                        </span>
                    </div>

                    <dl class="mt-4 flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400">
                        <div class="flex items-center gap-1.5">
                            <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                            {{ number_format($application->downloads_count) }}
                        </div>
                        <div class="flex items-center gap-1.5">
                            <x-heroicon-o-squares-2x2 class="h-4 w-4" />
                            {{ $application->live_releases_count }} / 3 platforms live
                        </div>
                    </dl>
                </a>
            @endforeach
        </div>

        <div class="mt-6">{{ $applications->links() }}</div>
    @endif

    <x-modal :show="$showCreate" wire:click.outside="$set('showCreate', false)" maxWidth="lg">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">New application</h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">You can change any of this later. Nothing is public until you publish your first build.</p>

        <div class="mt-4 space-y-4">
            <div>
                <label class="label" for="app-name">Name</label>
                <input id="app-name" type="text" wire:model="name" class="input" placeholder="e.g. Harbor Notes">
                @error('name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label" for="app-tagline">Tagline <span class="font-normal text-slate-400">(optional)</span></label>
                <input id="app-tagline" type="text" wire:model="tagline" class="input" placeholder="One line describing what it does">
                @error('tagline') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label" for="app-description">Description <span class="font-normal text-slate-400">(optional)</span></label>
                <textarea id="app-description" wire:model="description" rows="3" class="input" placeholder="Shown on the public download page."></textarea>
                @error('description') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label" for="app-website">Website <span class="font-normal text-slate-400">(optional)</span></label>
                <input id="app-website" type="url" wire:model="website_url" class="input" placeholder="https://example.com">
                @error('website_url') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <button wire:click="$set('showCreate', false)" class="btn-secondary">Cancel</button>
            <button wire:click="create" wire:loading.attr="disabled" class="btn-primary">Create application</button>
        </div>
    </x-modal>
</div>
