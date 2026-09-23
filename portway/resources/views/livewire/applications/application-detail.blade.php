<div>
    {{-- Header --------------------------------------------------------- --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <div class="flex items-center gap-2">
                <a href="{{ route('applications.index') }}" wire:navigate class="btn-ghost !px-2 !py-1">
                    <x-heroicon-o-arrow-left class="h-4 w-4" />
                </a>
                <h1 class="truncate text-xl font-semibold text-slate-900 dark:text-white">{{ $application->name }}</h1>
                @if ($publicUrl)
                    <span class="badge-success">Live</span>
                @else
                    <span class="badge-neutral">Not published</span>
                @endif
            </div>
            @if ($application->tagline)
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $application->tagline }}</p>
            @endif
        </div>

        @if ($publicUrl)
            <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="btn-secondary">
                <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4" /> View download page
            </a>
        @endif
    </div>

    {{-- Tabs ------------------------------------------------------------ --}}
    <div class="mb-6 overflow-x-auto">
        <div class="flex gap-1 border-b border-surface-200 dark:border-white/10">
            @foreach ([
                'releases' => 'Builds',
                'archive' => 'Archive',
                'settings' => 'Settings',
            ] as $key => $label)
                <button
                    wire:click="setTab('{{ $key }}')"
                    class="whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium {{ $tab === $key ? 'border-harbor-600 text-harbor-700 dark:border-harbor-400 dark:text-harbor-300' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}"
                >
                    {{ $label }}
                    @if ($key === 'archive' && $archivedReleases->count() > 0)
                        <span class="ml-1 text-xs text-slate-400">{{ $archivedReleases->count() }}</span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    {{-- Builds tab ------------------------------------------------------ --}}
    @if ($tab === 'releases')
        @if ($draftCount > 0)
            <div class="card mb-6 border-harbor-200 dark:border-harbor-500/20">
                <div class="flex items-start gap-3">
                    <x-heroicon-o-information-circle class="h-5 w-5 flex-shrink-0 text-harbor-600 dark:text-harbor-400" />
                    <div>
                        <p class="font-medium text-slate-900 dark:text-white">{{ $draftCount }} {{ Str::plural('build', $draftCount) }} uploaded but not published</p>
                        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                            A build stays private until you publish it. Publishing replaces whatever is live for that platform.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <div class="space-y-6">
            @foreach ($platforms as $platform)
                @php
                    $live = $liveByPlatform->get($platform->value);
                    $all = $releasesByPlatform->get($platform->value, collect());
                    $drafts = $all->where('status', \App\Enums\ReleaseStatus::Draft);
                @endphp

                <div class="card" wire:key="platform-{{ $platform->value }}">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-surface-100 text-slate-600 dark:bg-white/5 dark:text-slate-300">
                                <x-dynamic-component :component="'heroicon-o-'.$platform->icon()" class="h-5 w-5" />
                            </span>
                            <div>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ $platform->label() }}</p>
                                @if ($live)
                                    <p class="text-sm text-emerald-600 dark:text-emerald-400">
                                        Version {{ $live->version }} is live · {{ number_format($live->downloads_count) }} downloads
                                    </p>
                                @else
                                    <p class="text-sm text-slate-500 dark:text-slate-400">
                                        Nothing published — this platform is hidden from your download page.
                                    </p>
                                @endif
                            </div>
                        </div>

                        <button wire:click="openUpload('{{ $platform->value }}')" class="btn-secondary">
                            <x-heroicon-o-arrow-up-tray class="h-4 w-4" /> Upload build
                        </button>
                    </div>

                    @if ($live)
                        <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50/50 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="badge-success">Live</span>
                                        <span class="font-mono text-sm font-medium text-slate-900 dark:text-white">{{ $live->version }}</span>
                                        @if ($live->architecture)
                                            <span class="badge-neutral">{{ $live->architecture }}</span>
                                        @endif
                                    </div>
                                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                        {{ $live->sizeHuman() }} ·
                                        SHA-256 <span class="font-mono">{{ $live->shortChecksum() }}</span> ·
                                        published {{ $live->published_at?->diffForHumans() ?? '—' }}
                                    </p>
                                    @if ($live->changelog)
                                        <p class="mt-2 max-w-2xl whitespace-pre-line text-sm text-slate-600 dark:text-slate-300">{{ Str::limit($live->changelog, 280) }}</p>
                                    @endif
                                </div>

                                <div class="flex flex-shrink-0 items-center gap-2">
                                    <a href="{{ route('releases.download', $live) }}" class="btn-ghost !px-2 !py-1.5" title="Download this build">
                                        <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                                    </a>
                                    <button wire:click="confirmTakeOffline({{ $live->id }})" class="btn-secondary !px-3 !py-1.5 text-xs">Take offline</button>
                                    <button wire:click="confirmDelete({{ $live->id }})" class="btn-ghost !p-1.5 text-rose-600" title="Delete this build">
                                        <x-heroicon-o-trash class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($drafts->isNotEmpty())
                        <div class="mt-4 space-y-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Ready to publish</p>
                            @foreach ($drafts as $draft)
                                <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-surface-200 p-3 dark:border-white/10" wire:key="draft-{{ $draft->id }}">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="badge-info">Draft</span>
                                            <span class="font-mono text-sm text-slate-900 dark:text-white">{{ $draft->version }}</span>
                                        </div>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                            {{ $draft->sizeHuman() }} · uploaded {{ $draft->created_at->diffForHumans() }}
                                        </p>
                                        @unless ($draft->isReadyToPublish())
                                            <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                                Missing {{ implode(', ', $draft->missingPublishRequirements()) }}.
                                            </p>
                                        @endunless
                                    </div>
                                    <div class="flex flex-shrink-0 items-center gap-2">
                                        <button
                                            wire:click="confirmPublish({{ $draft->id }})"
                                            @disabled(! $draft->isReadyToPublish())
                                            class="btn-primary !px-3 !py-1.5 text-xs"
                                        >
                                            Publish
                                        </button>
                                        <button wire:click="confirmDelete({{ $draft->id }})" class="btn-ghost !p-1.5 text-rose-600" title="Delete this build">
                                            <x-heroicon-o-trash class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Archive tab ------------------------------------------------------ --}}
    @if ($tab === 'archive')
        @if ($archivedReleases->isEmpty())
            <x-empty-state
                icon="archive-box"
                title="No archived builds"
                description="When you publish a new build, the one it replaces is archived here. Archived builds stay private to you, and you can put one back online at any time."
            />
        @else
            <div class="card overflow-hidden !p-0">
                <table class="w-full text-sm">
                    <thead class="border-b border-surface-200 bg-surface-50 text-left text-xs uppercase tracking-wide text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Version</th>
                            <th class="px-4 py-3">Platform</th>
                            <th class="px-4 py-3">Size</th>
                            <th class="px-4 py-3">Archived</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-100 dark:divide-white/5">
                        @foreach ($archivedReleases as $release)
                            <tr wire:key="archived-{{ $release->id }}">
                                <td class="px-4 py-3 font-mono text-xs text-slate-800 dark:text-slate-100">{{ $release->version }}</td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $release->platform->label() }}</td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $release->sizeHuman() }}</td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $release->archived_at?->diffForHumans() ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('releases.download', $release) }}" class="btn-ghost !p-1.5" title="Download this build">
                                            <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                                        </a>
                                        <button wire:click="confirmPublish({{ $release->id }})" class="btn-secondary !px-3 !py-1.5 text-xs">
                                            Make live again
                                        </button>
                                        <button wire:click="confirmDelete({{ $release->id }})" class="btn-ghost !p-1.5 text-rose-600" title="Delete this build">
                                            <x-heroicon-o-trash class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif

    {{-- Settings tab ----------------------------------------------------- --}}
    @if ($tab === 'settings')
        <div class="card max-w-2xl">
            <h3 class="font-semibold text-slate-900 dark:text-white">Application details</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">This is what visitors see on the public download page.</p>

            <div class="mt-5 space-y-4">
                <div>
                    <label class="label" for="set-name">Name</label>
                    <input id="set-name" type="text" wire:model="name" class="input">
                    @error('name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="set-tagline">Tagline</label>
                    <input id="set-tagline" type="text" wire:model="tagline" class="input">
                    @error('tagline') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="set-description">Description</label>
                    <textarea id="set-description" wire:model="description" rows="4" class="input"></textarea>
                    @error('description') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="set-website">Website</label>
                        <input id="set-website" type="url" wire:model="website_url" class="input" placeholder="https://example.com">
                        @error('website_url') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="set-support">Support email</label>
                        <input id="set-support" type="email" wire:model="support_email" class="input">
                        @error('support_email') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <label class="flex items-start gap-3 rounded-lg border border-surface-200 p-4 dark:border-white/10">
                    <input type="checkbox" wire:model="is_listed" class="mt-0.5 rounded border-surface-300 text-harbor-600 focus:ring-harbor-500">
                    <span>
                        <span class="block text-sm font-medium text-slate-800 dark:text-slate-100">Show the public download page</span>
                        <span class="mt-0.5 block text-sm text-slate-500 dark:text-slate-400">
                            Turn this off to take the whole listing offline without touching your builds. Your live builds stay live the moment you turn it back on.
                        </span>
                    </span>
                </label>
            </div>

            <div class="mt-6 flex justify-end">
                <button wire:click="saveSettings" wire:loading.attr="disabled" class="btn-primary">Save changes</button>
            </div>
        </div>
    @endif

    {{-- Upload modal ----------------------------------------------------- --}}
    @php $uploadPlatformEnum = \App\Enums\ReleasePlatform::tryFrom($uploadPlatform); @endphp
    <x-modal :show="$showUpload" wire:click.outside="$set('showUpload', false)" maxWidth="2xl">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
            Upload a {{ $uploadPlatformEnum?->label() ?? 'build' }} build
        </h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Upload the installer with its details first. It is saved as a draft — you publish it as a separate step, and only then does it replace the live build.
        </p>

        <div class="mt-4 space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="up-version">Version</label>
                    <input id="up-version" type="text" wire:model="version" class="input" placeholder="1.4.2">
                    @error('version') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="up-arch">Architecture <span class="font-normal text-slate-400">(optional)</span></label>
                    <input id="up-arch" type="text" wire:model="architecture" class="input" placeholder="x64, arm64…">
                    @error('architecture') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="label" for="up-minos">Minimum OS version <span class="font-normal text-slate-400">(optional)</span></label>
                <input id="up-minos" type="text" wire:model="minimum_os" class="input" placeholder="{{ $uploadPlatformEnum === \App\Enums\ReleasePlatform::Windows ? 'Windows 10 or later' : 'e.g. macOS 13' }}">
                @error('minimum_os') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label" for="up-changelog">Release notes</label>
                <textarea id="up-changelog" wire:model="changelog" rows="4" class="input" placeholder="What changed in this version?"></textarea>
                @error('changelog') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label" for="up-file">Installer file</label>
                <input id="up-file" type="file" wire:model="buildFile" class="input !py-2">
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Accepted for {{ $uploadPlatformEnum?->label() }}: {{ $uploadPlatformEnum?->extensionHint() }}
                </p>
                <div wire:loading wire:target="buildFile" class="mt-2 text-sm text-slate-500 dark:text-slate-400">Uploading…</div>
                @error('buildFile') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <button wire:click="$set('showUpload', false)" class="btn-secondary">Cancel</button>
            <button wire:click="upload" wire:loading.attr="disabled" wire:target="upload,buildFile" class="btn-primary">
                Save as draft
            </button>
        </div>
    </x-modal>

    {{-- Publish confirmation --------------------------------------------- --}}
    @php $publishTarget = $publishTargetId ? $releasesByPlatform->flatten()->firstWhere('id', $publishTargetId) : null; @endphp
    <x-modal :show="(bool) $publishTargetId" wire:click.outside="$set('publishTargetId', null)">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Publish this build?</h3>
        @if ($publishTarget)
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                Version <span class="font-mono">{{ $publishTarget->version }}</span> becomes the download everyone gets for
                {{ $publishTarget->platform->label() }}.
                @php $current = $liveByPlatform->get($publishTarget->platform->value); @endphp
                @if ($current && $current->id !== $publishTarget->id)
                    Version <span class="font-mono">{{ $current->version }}</span> moves to your archive and disappears from the public page.
                @endif
            </p>
        @endif
        <div class="mt-6 flex justify-end gap-2">
            <button wire:click="$set('publishTargetId', null)" class="btn-secondary">Cancel</button>
            <button wire:click="publish" wire:loading.attr="disabled" class="btn-primary">Publish</button>
        </div>
    </x-modal>

    {{-- Take offline confirmation ----------------------------------------- --}}
    <x-modal :show="(bool) $takeOfflineTargetId" wire:click.outside="$set('takeOfflineTargetId', null)">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Take this platform offline?</h3>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
            The build moves to your archive and that platform stops appearing on your download page entirely — visitors will not see a broken link, they simply will not see that platform. You can put it back at any time.
        </p>
        <div class="mt-6 flex justify-end gap-2">
            <button wire:click="$set('takeOfflineTargetId', null)" class="btn-secondary">Cancel</button>
            <button wire:click="takeOffline" wire:loading.attr="disabled" class="btn-primary">Take offline</button>
        </div>
    </x-modal>

    {{-- Delete confirmation ------------------------------------------------ --}}
    <x-modal :show="(bool) $deleteTargetId" wire:click.outside="$set('deleteTargetId', null)">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Delete this build?</h3>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
            It disappears from your account and, if it was live, from your download page. It is not destroyed: it moves to the administrators' archive, where staff can restore it to you or remove it permanently.
        </p>
        <div class="mt-6 flex justify-end gap-2">
            <button wire:click="$set('deleteTargetId', null)" class="btn-secondary">Cancel</button>
            <button wire:click="delete" wire:loading.attr="disabled" class="btn-danger">Delete build</button>
        </div>
    </x-modal>
</div>
