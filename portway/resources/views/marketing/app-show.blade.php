@component('layouts.guest', ['title' => $application->name])

    @if ($isPreview)
        <div class="border-b border-amber-600/20 bg-amber-500 px-4 py-2.5 text-center text-sm font-medium text-white">
            Preview — this page is not visible to the public yet.
            @if (! $application->is_listed)
                The listing is switched off in your application settings.
            @else
                Publish a build to make it live.
            @endif
        </div>
    @endif

    {{-- Hero ------------------------------------------------------------ --}}
    <section class="relative isolate overflow-hidden">
        <div aria-hidden="true"
             class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[30rem] bg-gradient-to-b from-harbor-50 via-white to-white dark:from-harbor-500/10 dark:via-surface-950 dark:to-surface-950"></div>
        <div aria-hidden="true"
             class="pointer-events-none absolute -top-40 left-1/2 -z-10 h-72 w-[40rem] -translate-x-1/2 rounded-full bg-harbor-300/30 blur-3xl dark:bg-harbor-500/20"></div>

        <div class="mx-auto max-w-4xl px-4 pb-14 pt-10 text-center sm:px-6 sm:pb-20 sm:pt-14 lg:px-8">
            <a href="{{ route('apps.index') }}"
               class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-harbor-600 dark:text-slate-400 dark:hover:text-harbor-400">
                <x-heroicon-o-arrow-left class="h-4 w-4" />
                All apps
            </a>

            <h1 class="mt-6 text-4xl font-bold leading-[1.05] tracking-tight text-slate-900 dark:text-white sm:text-6xl">
                {{ $application->name }}
            </h1>

            @if ($application->tagline)
                <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-slate-600 dark:text-slate-400 sm:text-xl">
                    {{ $application->tagline }}
                </p>
            @endif

            @if ($downloads->isNotEmpty())
                @php
                    $primary = ($suggested && $downloads->has($suggested->value))
                        ? $downloads->get($suggested->value)
                        : $downloads->first();
                @endphp

                <div class="mt-10 flex flex-col items-center gap-4">
                    <a href="{{ route('apps.download', ['application' => $application->slug, 'platform' => $primary->platform->value]) }}"
                       class="btn-primary px-7 py-3.5 text-base shadow-lift">
                        <x-heroicon-o-arrow-down-tray class="h-5 w-5" />
                        Download for {{ $primary->platform->label() }}
                    </a>

                    <p class="flex flex-wrap items-center justify-center gap-x-2 gap-y-1 text-sm text-slate-500 dark:text-slate-400">
                        <span>Version <span class="font-mono text-slate-700 dark:text-slate-200">{{ $primary->version }}</span></span>
                        <span aria-hidden="true" class="text-slate-300 dark:text-slate-600">&middot;</span>
                        <span>{{ $primary->sizeHuman() }}</span>
                        @if ($primary->minimum_os)
                            <span aria-hidden="true" class="text-slate-300 dark:text-slate-600">&middot;</span>
                            <span>{{ $primary->minimum_os }}</span>
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </section>

    {{-- Platform downloads ---------------------------------------------- --}}
    {{-- Only platforms with a live build render at all: an unpublished
         platform is absent rather than shown as unavailable. --}}
    @if ($downloads->isNotEmpty())
        <section class="border-t border-surface-200 bg-surface-50 py-16 dark:border-white/10 dark:bg-white/[0.02] sm:py-20">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-harbor-600 dark:text-harbor-400">Get it</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl">Downloads</h2>
                    <p class="mt-4 text-slate-600 dark:text-slate-400">
                        Always the current release. {{ number_format($application->downloads_count) }} downloads so far.
                    </p>
                </div>

                {{-- Column counts are written out in full rather than
                     interpolated, so Tailwind's scanner actually emits them. --}}
                @php
                    $columnClass = match (min($downloads->count(), 3)) {
                        1 => 'lg:grid-cols-1',
                        2 => 'lg:grid-cols-2',
                        default => 'lg:grid-cols-3',
                    };
                @endphp
                <div class="mx-auto mt-12 grid max-w-4xl gap-5 sm:grid-cols-2 {{ $columnClass }}">
                    @foreach ($downloads as $release)
                        <div class="card flex flex-col p-6 transition hover:shadow-lift">
                            <div class="flex items-center gap-3">
                                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-harbor-50 text-harbor-600 dark:bg-harbor-500/10 dark:text-harbor-300">
                                    <x-dynamic-component :component="'heroicon-o-'.$release->platform->icon()" class="h-6 w-6" />
                                </span>
                                <h3 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-white">{{ $release->platform->label() }}</h3>
                            </div>

                            <dl class="mt-5 space-y-2.5 border-t border-surface-200 pt-5 text-sm text-slate-500 dark:border-white/10 dark:text-slate-400">
                                <div class="flex justify-between gap-3">
                                    <dt>Version</dt>
                                    <dd class="font-mono text-slate-700 dark:text-slate-200">{{ $release->version }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt>Size</dt>
                                    <dd class="text-slate-700 dark:text-slate-200">{{ $release->sizeHuman() }}</dd>
                                </div>
                                @if ($release->architecture)
                                    <div class="flex justify-between gap-3">
                                        <dt>Architecture</dt>
                                        <dd class="text-slate-700 dark:text-slate-200">{{ $release->architecture }}</dd>
                                    </div>
                                @endif
                                @if ($release->minimum_os)
                                    <div class="flex justify-between gap-3">
                                        <dt>Requires</dt>
                                        <dd class="text-right text-slate-700 dark:text-slate-200">{{ $release->minimum_os }}</dd>
                                    </div>
                                @endif
                                <div class="flex justify-between gap-3">
                                    <dt>Released</dt>
                                    <dd class="text-slate-700 dark:text-slate-200">{{ $release->published_at?->format('j M Y') ?? '—' }}</dd>
                                </div>
                            </dl>

                            <a href="{{ route('apps.download', ['application' => $application->slug, 'platform' => $release->platform->value]) }}"
                               class="btn-primary mt-6 w-full py-2.5">
                                <x-heroicon-o-arrow-down-tray class="h-4 w-4" /> Download
                            </a>

                            @if ($release->checksum_sha256)
                                <div class="mt-4 rounded-xl border border-surface-200 bg-surface-50 px-3 py-2.5 dark:border-white/10 dark:bg-white/[0.03]">
                                    <p class="flex items-center justify-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400 dark:text-slate-500">
                                        <x-heroicon-o-finger-print class="h-3.5 w-3.5" /> SHA-256
                                    </p>
                                    <p class="mt-1.5 break-all text-center font-mono text-[11px] leading-relaxed text-slate-500 dark:text-slate-400">
                                        {{ $release->checksum_sha256 }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- About + release notes --------------------------------------------- --}}
    <section class="border-t border-surface-200 py-16 dark:border-white/10 sm:py-20">
        <div class="mx-auto grid max-w-5xl gap-10 px-4 sm:px-6 lg:grid-cols-3 lg:gap-14 lg:px-8">
            @if ($application->description)
                <div class="lg:col-span-2">
                    <h2 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-white">About</h2>
                    <p class="mt-4 whitespace-pre-line leading-relaxed text-slate-600 dark:text-slate-300">{{ $application->description }}</p>
                </div>
            @endif

            <div class="{{ $application->description ? '' : 'lg:col-span-3' }}">
                @if ($downloads->isNotEmpty())
                    <h2 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-white">What's new</h2>
                    <div class="mt-4 space-y-4">
                        @foreach ($downloads as $release)
                            @if ($release->changelog)
                                <div class="card p-5">
                                    <p class="flex flex-wrap items-center gap-2 text-sm font-semibold text-slate-800 dark:text-slate-100">
                                        <x-dynamic-component :component="'heroicon-o-'.$release->platform->icon()" class="h-4 w-4 text-harbor-600 dark:text-harbor-400" />
                                        {{ $release->platform->label() }}
                                        <span class="badge-neutral font-mono">{{ $release->version }}</span>
                                    </p>
                                    <p class="mt-2.5 whitespace-pre-line text-sm leading-relaxed text-slate-500 dark:text-slate-400">{{ $release->changelog }}</p>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                @if ($application->website_url || $application->support_email)
                    <h2 class="mt-10 text-xl font-semibold tracking-tight text-slate-900 dark:text-white">Links</h2>
                    <ul class="mt-4 space-y-3 text-sm">
                        @if ($application->website_url)
                            <li>
                                <a href="{{ $application->website_url }}" rel="noopener nofollow external" target="_blank"
                                   class="inline-flex items-center gap-1.5 font-medium text-harbor-600 hover:underline dark:text-harbor-400">
                                    <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4" />
                                    Official website
                                </a>
                            </li>
                        @endif
                        @if ($application->support_email)
                            <li class="inline-flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                                <x-heroicon-o-envelope class="h-4 w-4 flex-shrink-0" />
                                Support: {{ $application->support_email }}
                            </li>
                        @endif
                    </ul>
                @endif
            </div>
        </div>
    </section>

    {{-- Footnote: what a listing on Portway means ------------------------- --}}
    <section class="border-t border-surface-200 bg-surface-50 py-14 dark:border-white/10 dark:bg-white/[0.02]">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-6 sm:grid-cols-3">
                @foreach ([
                    ['icon' => 'arrow-path', 'title' => 'Current release only', 'body' => 'Each platform has exactly one live build. Publishing a newer one replaces it.'],
                    ['icon' => 'finger-print', 'title' => 'Verifiable downloads', 'body' => 'Where the publisher supplied one, the SHA-256 checksum is shown next to the file.'],
                    ['icon' => 'cube', 'title' => 'Hosted on ' . config('app.brand.name'), 'body' => 'Published by an account here. Portway hosts the files; the publisher writes the app.'],
                ] as $note)
                    <div>
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-white text-harbor-600 shadow-soft dark:bg-surface-900 dark:text-harbor-300">
                            <x-dynamic-component :component="'heroicon-o-'.$note['icon']" class="h-5 w-5" />
                        </span>
                        <h2 class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">{{ $note['title'] }}</h2>
                        <p class="mt-1 text-sm leading-relaxed text-slate-500 dark:text-slate-400">{{ $note['body'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-10 text-center">
                <a href="{{ route('apps.index') }}" class="btn-secondary">
                    <x-heroicon-o-arrow-left class="h-4 w-4" />
                    Back to all apps
                </a>
            </div>
        </div>
    </section>

@endcomponent
