@component('layouts.guest', ['title' => 'Apps'])

    {{-- Hero -------------------------------------------------------------- --}}
    <section class="relative isolate overflow-hidden">
        <div aria-hidden="true"
             class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[28rem] bg-gradient-to-b from-harbor-50 via-white to-white dark:from-harbor-500/10 dark:via-surface-950 dark:to-surface-950"></div>
        <div aria-hidden="true"
             class="pointer-events-none absolute -top-40 left-1/2 -z-10 h-72 w-[40rem] -translate-x-1/2 rounded-full bg-harbor-300/30 blur-3xl dark:bg-harbor-500/20"></div>

        <div class="mx-auto max-w-4xl px-4 pb-12 pt-16 text-center sm:px-6 sm:pb-16 sm:pt-24 lg:px-8">
            <span class="inline-flex items-center gap-2 rounded-full border border-harbor-200/70 bg-white/70 px-3.5 py-1.5 text-sm font-medium text-harbor-700 shadow-soft backdrop-blur dark:border-harbor-500/20 dark:bg-harbor-500/10 dark:text-harbor-300">
                <x-heroicon-o-cube class="h-4 w-4" />
                App directory
            </span>

            <h1 class="mt-6 text-4xl font-bold leading-[1.05] tracking-tight text-slate-900 dark:text-white sm:text-6xl">
                Apps built on {{ config('app.brand.name') }}
            </h1>

            <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-slate-600 dark:text-slate-400 sm:text-xl">
                Desktop software published by people hosting here. Every listing serves the current release —
                nothing stale, no dead links.
            </p>
        </div>
    </section>

    {{-- Directory ----------------------------------------------------------- --}}
    <section class="border-t border-surface-200 bg-surface-50 py-16 dark:border-white/10 dark:bg-white/[0.02] sm:py-20">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            @if ($applications->isEmpty())
                <x-empty-state
                    icon="cube"
                    title="Nothing published yet"
                    description="Applications appear here as soon as their authors publish a build."
                />
            @else
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($applications as $application)
                        <a href="{{ route('apps.show', $application) }}"
                           class="card group flex h-full flex-col p-6 transition duration-200 hover:-translate-y-0.5 hover:border-harbor-200 hover:shadow-lift dark:hover:border-harbor-500/30">
                            <div class="flex items-start justify-between gap-3">
                                <h2 class="text-lg font-semibold tracking-tight text-slate-900 transition group-hover:text-harbor-700 dark:text-white dark:group-hover:text-harbor-300">
                                    {{ $application->name }}
                                </h2>
                                <x-heroicon-o-arrow-right class="mt-1 h-4 w-4 flex-shrink-0 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-harbor-600 dark:text-slate-600 dark:group-hover:text-harbor-400" />
                            </div>

                            @if ($application->tagline)
                                <p class="mt-2 text-sm leading-relaxed text-slate-500 dark:text-slate-400">{{ $application->tagline }}</p>
                            @endif

                            <div class="mt-5 flex flex-wrap items-center gap-2">
                                @foreach ($application->activeReleases as $release)
                                    <span class="badge-neutral">
                                        <x-dynamic-component :component="'heroicon-o-'.$release->platform->icon()" class="h-3.5 w-3.5" />
                                        {{ $release->platform->label() }}
                                    </span>
                                @endforeach
                            </div>

                            <div class="mt-auto flex items-center gap-1.5 border-t border-surface-200 pt-4 text-xs text-slate-400 dark:border-white/10 dark:text-slate-500">
                                <x-heroicon-o-arrow-down-tray class="h-3.5 w-3.5" />
                                {{ number_format($application->downloads_count) }} downloads
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-10">{{ $applications->links() }}</div>
            @endif
        </div>
    </section>

    {{-- Publish your own ------------------------------------------------------ --}}
    <section class="border-t border-surface-200 py-16 dark:border-white/10 sm:py-20">
        <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl">
                Publishing here is free too.
            </h2>
            <p class="mx-auto mt-4 max-w-2xl text-lg leading-relaxed text-slate-600 dark:text-slate-400">
                Upload a Windows, macOS or Linux build and {{ config('app.brand.name') }} gives it a download page with
                the version, size, requirements, release notes and a SHA-256 checksum. The newest published build for a
                platform is always the one visitors get.
            </p>
            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-primary w-full px-7 py-3.5 text-base shadow-lift sm:w-auto">
                        Go to dashboard
                        <x-heroicon-o-arrow-right class="h-4 w-4" />
                    </a>
                @else
                    <a href="{{ route('register') }}" class="btn-primary w-full px-7 py-3.5 text-base shadow-lift sm:w-auto">
                        Create a free account
                        <x-heroicon-o-arrow-right class="h-4 w-4" />
                    </a>
                @endauth
                <a href="{{ route('features') }}" class="btn-secondary w-full px-7 py-3.5 text-base sm:w-auto">See all features</a>
            </div>
        </div>
    </section>

@endcomponent
