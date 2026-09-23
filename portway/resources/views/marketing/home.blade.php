@component('layouts.guest')

    {{-- Hero -------------------------------------------------------------- --}}
    <section class="relative isolate overflow-hidden">
        <div aria-hidden="true"
             class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[38rem] bg-gradient-to-b from-harbor-50 via-white to-white dark:from-harbor-500/10 dark:via-surface-950 dark:to-surface-950"></div>
        <div aria-hidden="true"
             class="pointer-events-none absolute -top-40 left-1/2 -z-10 h-80 w-[46rem] -translate-x-1/2 rounded-full bg-harbor-300/30 blur-3xl dark:bg-harbor-500/20"></div>

        <div class="mx-auto max-w-5xl px-4 pb-16 pt-16 text-center sm:px-6 sm:pb-24 sm:pt-24 lg:px-8">
            <span class="inline-flex items-center gap-2 rounded-full border border-harbor-200/70 bg-white/70 px-3.5 py-1.5 text-sm font-medium text-harbor-700 shadow-soft backdrop-blur dark:border-harbor-500/20 dark:bg-harbor-500/10 dark:text-harbor-300">
                <x-heroicon-o-sparkles class="h-4 w-4" />
                Free forever &middot; no credit card, ever
            </span>

            <h1 class="mt-6 text-4xl font-bold leading-[1.05] tracking-tight text-slate-900 dark:text-white sm:text-6xl lg:text-7xl">
                Host your site.<br class="hidden sm:block">
                <span class="text-harbor-600 dark:text-harbor-400">Ship your app.</span>
                Pay nothing.
            </h1>

            <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-slate-600 dark:text-slate-400 sm:text-xl">
                Portway gives every account free PHP &amp; Node.js hosting, a real MySQL database, a free SSL certificate
                and the developer tools you'd expect from a paid host — and a public download page for the desktop
                builds you publish.
            </p>

            <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ route('register') }}" class="btn-primary w-full px-7 py-3.5 text-base shadow-lift sm:w-auto">
                    Start hosting free
                    <x-heroicon-o-arrow-right class="h-4 w-4" />
                </a>
                <a href="{{ route('apps.index') }}" class="btn-secondary w-full px-7 py-3.5 text-base sm:w-auto">
                    <x-heroicon-o-cube class="h-4 w-4" />
                    Browse published apps
                </a>
            </div>

            {{-- Honest product facts, not invented metrics. --}}
            <dl class="mx-auto mt-14 grid max-w-3xl grid-cols-2 gap-px overflow-hidden rounded-xl2 border border-surface-200 bg-surface-200 text-left shadow-soft dark:border-white/10 dark:bg-white/10 sm:grid-cols-4">
                @foreach ([
                    ['term' => '10 GB', 'detail' => 'default storage'],
                    ['term' => 'Free SSL', 'detail' => 'auto-renewing'],
                    ['term' => 'PHP + Node', 'detail' => 'side by side'],
                    ['term' => 'No expiry', 'detail' => 'no trial clock'],
                ] as $fact)
                    <div class="bg-white px-5 py-4 dark:bg-surface-900">
                        <dt class="text-base font-semibold tracking-tight text-slate-900 dark:text-white">{{ $fact['term'] }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ $fact['detail'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- Two product areas -------------------------------------------------- --}}
    <section class="border-t border-surface-200 py-20 dark:border-white/10 sm:py-28">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-harbor-600 dark:text-harbor-400">Two ways to use Portway</p>
            <h2 class="mt-3 max-w-2xl text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl">
                One account. A place to run the web app, and a place to hand out the desktop one.
            </h2>

            <div class="mt-12 grid gap-6 lg:grid-cols-2">
                <div class="card flex flex-col gap-4 p-7 transition hover:shadow-lift">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-harbor-50 text-harbor-600 dark:bg-harbor-500/10 dark:text-harbor-300">
                        <x-heroicon-o-globe-alt class="h-6 w-6" />
                    </span>
                    <h3 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-white">Web hosting</h3>
                    <p class="text-slate-600 dark:text-slate-400">
                        Launch a PHP or Node.js site in seconds, import one from Git, connect a domain from any
                        registrar, and manage databases, files, cron jobs and backups from a single dashboard.
                    </p>
                    <ul class="mt-auto space-y-2 pt-2 text-sm text-slate-600 dark:text-slate-300">
                        @foreach (['Free auto-renewing SSL on every site', 'Real MySQL with a browser-based manager', 'Git deploys, web terminal, cron jobs'] as $point)
                            <li class="flex items-start gap-2">
                                <x-heroicon-o-check-circle class="mt-0.5 h-4 w-4 flex-shrink-0 text-harbor-500 dark:text-harbor-400" />
                                {{ $point }}
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('features') }}" class="btn-secondary mt-4 self-start">
                        See what's included
                        <x-heroicon-o-arrow-right class="h-4 w-4" />
                    </a>
                </div>

                <div class="card flex flex-col gap-4 p-7 transition hover:shadow-lift">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-beacon-50 text-beacon-600 dark:bg-beacon-500/10 dark:text-beacon-400">
                        <x-heroicon-o-cube class="h-6 w-6" />
                    </span>
                    <h3 class="flex flex-wrap items-center gap-2 text-xl font-semibold tracking-tight text-slate-900 dark:text-white">
                        App distribution
                        <span class="badge bg-beacon-50 text-beacon-700 dark:bg-beacon-500/10 dark:text-beacon-400">New</span>
                    </h3>
                    <p class="text-slate-600 dark:text-slate-400">
                        Upload a Windows, macOS or Linux build and Portway gives it a public download page — with the
                        version, size, requirements, release notes and a SHA-256 checksum laid out for visitors.
                    </p>
                    <ul class="mt-auto space-y-2 pt-2 text-sm text-slate-600 dark:text-slate-300">
                        @foreach (['Exactly one live build per platform', 'Unpublished platforms are simply absent', 'Download counts on every listing'] as $point)
                            <li class="flex items-start gap-2">
                                <x-heroicon-o-check-circle class="mt-0.5 h-4 w-4 flex-shrink-0 text-beacon-500 dark:text-beacon-400" />
                                {{ $point }}
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('apps.index') }}" class="btn-secondary mt-4 self-start">
                        Browse the app directory
                        <x-heroicon-o-arrow-right class="h-4 w-4" />
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Feature grid ------------------------------------------------------- --}}
    <section class="border-t border-surface-200 bg-surface-50 py-20 dark:border-white/10 dark:bg-white/[0.02] sm:py-28">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-harbor-600 dark:text-harbor-400">Everything in one dashboard</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl">
                    No separate control panel logins. No upsells.
                </h2>
                <p class="mt-4 text-lg text-slate-600 dark:text-slate-400">
                    One clean interface for every part of managing a website — and none of it is a paid add-on.
                </p>
            </div>

            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['icon' => 'globe-alt', 'title' => 'Instant websites', 'body' => 'Spin up a PHP or Node.js site in seconds, or import one from Git. Every site gets a free temporary address immediately.'],
                    ['icon' => 'link', 'title' => 'Bring your own domain', 'body' => 'Connect a domain from any registrar with guided DNS steps, or use the free subdomain we give every account.'],
                    ['icon' => 'shield-check', 'title' => 'Free SSL, always on', 'body' => 'Certificates are issued and renewed automatically. Force HTTPS with one toggle.'],
                    ['icon' => 'circle-stack', 'title' => 'Real MySQL databases', 'body' => 'Create databases and users, and manage them with a built-in browser-based database manager.'],
                    ['icon' => 'folder', 'title' => 'File manager with code editor', 'body' => 'Upload, edit, zip, and organize your files right in the browser — with syntax highlighting for code.'],
                    ['icon' => 'code-bracket-square', 'title' => 'Git-based deploys', 'body' => 'Connect a repository and deploy new commits on demand, with a full build log for every deployment.'],
                    ['icon' => 'command-line', 'title' => 'Web terminal', 'body' => 'Run composer, npm, artisan, and wp-cli commands directly against your site, sandboxed for safety.'],
                    ['icon' => 'clock', 'title' => 'Cron jobs', 'body' => 'Schedule recurring tasks with common presets or your own cron expression.'],
                    ['icon' => 'archive-box', 'title' => 'One-click backups', 'body' => 'Create and restore full backups of your files and database whenever you need to.'],
                ] as $feature)
                    <div class="card group h-full transition duration-200 hover:-translate-y-0.5 hover:shadow-lift">
                        <span class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-harbor-50 text-harbor-600 transition group-hover:bg-harbor-600 group-hover:text-white dark:bg-harbor-500/10 dark:text-harbor-300 dark:group-hover:bg-harbor-500 dark:group-hover:text-white">
                            <x-dynamic-component :component="'heroicon-o-'.$feature['icon']" class="h-5 w-5" />
                        </span>
                        <h3 class="font-semibold tracking-tight text-slate-900 dark:text-white">{{ $feature['title'] }}</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-slate-500 dark:text-slate-400">{{ $feature['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- App distribution, in detail ---------------------------------------- --}}
    <section class="relative overflow-hidden border-t border-surface-200 py-20 dark:border-white/10 sm:py-28">
        <div class="mx-auto grid max-w-6xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-beacon-600 dark:text-beacon-400">App distribution</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl">
                    A download page that is never out of date.
                </h2>
                <p class="mt-4 text-lg leading-relaxed text-slate-600 dark:text-slate-400">
                    Publish a build and it becomes the live one for that platform. Publish a newer one and it takes
                    over. Visitors only ever see the current release, so there is no stale link to clean up later.
                </p>

                <ul class="mt-8 space-y-5">
                    @foreach ([
                        ['icon' => 'arrow-up-tray', 'title' => 'Upload per platform', 'body' => 'Windows, macOS and Linux builds, each with its own version, architecture and minimum OS.'],
                        ['icon' => 'finger-print', 'title' => 'Checksums published for you', 'body' => 'A SHA-256 checksum is shown on the download page so people can verify what they just fetched.'],
                        ['icon' => 'document-text', 'title' => 'Release notes in place', 'body' => 'Per-release changelogs appear under "What\'s new" on the same page as the download.'],
                    ] as $item)
                        <li class="flex gap-4">
                            <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-beacon-50 text-beacon-600 dark:bg-beacon-500/10 dark:text-beacon-400">
                                <x-dynamic-component :component="'heroicon-o-'.$item['icon']" class="h-5 w-5" />
                            </span>
                            <div>
                                <h3 class="font-semibold text-slate-900 dark:text-white">{{ $item['title'] }}</h3>
                                <p class="mt-1 text-sm leading-relaxed text-slate-500 dark:text-slate-400">{{ $item['body'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <a href="{{ route('apps.index') }}" class="btn-primary mt-9 px-6 py-3 text-base">
                    See the app directory
                    <x-heroicon-o-arrow-right class="h-4 w-4" />
                </a>
            </div>

            {{-- Illustrative, not a real listing: it shows the shape of a
                 release page rather than any specific app's data. --}}
            <div class="relative isolate">
                <div aria-hidden="true"
                     class="pointer-events-none absolute -inset-6 -z-10 rounded-xl2 bg-gradient-to-br from-harbor-100 to-beacon-50 opacity-70 blur-2xl dark:from-harbor-500/20 dark:to-beacon-500/10"></div>
                <div class="card p-6 shadow-lift">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">How a release page is laid out</p>
                    <div class="mt-5 space-y-3">
                        @foreach ([
                            ['icon' => 'computer-desktop', 'label' => 'Windows'],
                            ['icon' => 'command-line', 'label' => 'macOS'],
                            ['icon' => 'cpu-chip', 'label' => 'Linux'],
                        ] as $platform)
                            <div class="flex items-center gap-3 rounded-xl border border-surface-200 bg-surface-50 px-4 py-3 dark:border-white/10 dark:bg-white/[0.03]">
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-slate-600 shadow-soft dark:bg-surface-800 dark:text-slate-300">
                                    <x-dynamic-component :component="'heroicon-o-'.$platform['icon']" class="h-5 w-5" />
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $platform['label'] }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">One live build &middot; version, size, checksum</p>
                                </div>
                                <x-heroicon-o-arrow-down-tray class="ml-auto h-4 w-4 flex-shrink-0 text-harbor-600 dark:text-harbor-400" />
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-5 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                        A platform you haven't published for doesn't appear at all — no greyed-out button, no
                        "coming soon".
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- How it works -------------------------------------------------------- --}}
    <section class="border-t border-surface-200 bg-surface-50 py-20 dark:border-white/10 dark:bg-white/[0.02] sm:py-28">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-harbor-600 dark:text-harbor-400">Getting started</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl">Live in three steps</h2>
            </div>

            <div class="mt-14 grid gap-8 sm:grid-cols-3">
                @foreach ([
                    ['step' => '1', 'title' => 'Create your free account', 'body' => 'No payment details. Just a name, email, and password.'],
                    ['step' => '2', 'title' => 'Launch a website', 'body' => 'Start from scratch, import a Git repo, or upload your existing files.'],
                    ['step' => '3', 'title' => 'Connect your domain', 'body' => 'Point your domain at Portway, or just use the free address we give you.'],
                ] as $step)
                    <div class="relative">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-harbor-600 text-base font-bold text-white shadow-soft">{{ $step['step'] }}</span>
                        <h3 class="mt-5 text-lg font-semibold tracking-tight text-slate-900 dark:text-white">{{ $step['title'] }}</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-slate-500 dark:text-slate-400">{{ $step['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Why it's free / what's built in ------------------------------------- --}}
    <section class="border-t border-surface-200 py-20 dark:border-white/10 sm:py-28">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-12 lg:grid-cols-3 lg:gap-16">
                <div class="lg:col-span-1">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-harbor-600 dark:text-harbor-400">No catch</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl">
                        There is no pricing page, because there is no pricing.
                    </h2>
                    <p class="mt-4 text-lg leading-relaxed text-slate-600 dark:text-slate-400">
                        Portway has no billing, subscriptions or payment flow anywhere in the product — by design. There
                        is nothing to cancel and no feature held back behind a plan.
                    </p>
                    <p class="mt-4 text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                        Default limits (10 GB of storage, plus website, database and backup allowances) apply to every
                        account. Administrators can raise them for an account or change the defaults platform-wide.
                    </p>
                </div>

                <div class="grid gap-5 sm:grid-cols-2 lg:col-span-2">
                    @foreach ([
                        ['icon' => 'lock-closed', 'title' => 'Two-factor authentication', 'body' => 'TOTP apps plus recovery codes, on every account that wants it.'],
                        ['icon' => 'shield-check', 'title' => 'Tamper-resistant activity log', 'body' => 'A hash-chained record of what happened on your account, and when.'],
                        ['icon' => 'server-stack', 'title' => 'Sandboxed by default', 'body' => 'Terminal commands and file operations are allowlisted and scoped to your own site directories.'],
                        ['icon' => 'chart-bar', 'title' => 'Usage you can see', 'body' => 'Storage and bandwidth dashboards, with warnings before you hit a limit.'],
                    ] as $pillar)
                        <div class="card h-full transition hover:shadow-lift">
                            <span class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-harbor-50 text-harbor-600 dark:bg-harbor-500/10 dark:text-harbor-300">
                                <x-dynamic-component :component="'heroicon-o-'.$pillar['icon']" class="h-5 w-5" />
                            </span>
                            <h3 class="font-semibold tracking-tight text-slate-900 dark:text-white">{{ $pillar['title'] }}</h3>
                            <p class="mt-1.5 text-sm leading-relaxed text-slate-500 dark:text-slate-400">{{ $pillar['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Closing CTA ---------------------------------------------------------- --}}
    <section class="px-4 pb-20 sm:px-6 lg:px-8">
        <div class="relative isolate mx-auto max-w-6xl overflow-hidden rounded-xl2 bg-harbor-600 px-6 py-16 text-center shadow-lift sm:px-12 sm:py-20">
            <div aria-hidden="true"
                 class="pointer-events-none absolute -right-24 -top-24 -z-10 h-72 w-72 rounded-full bg-harbor-400/40 blur-3xl"></div>
            <div aria-hidden="true"
                 class="pointer-events-none absolute -bottom-32 -left-20 -z-10 h-72 w-72 rounded-full bg-harbor-800/50 blur-3xl"></div>

            <h2 class="mx-auto max-w-2xl text-3xl font-bold tracking-tight text-white sm:text-5xl">
                Ready when you are — for free.
            </h2>
            <p class="mx-auto mt-4 max-w-xl text-lg text-harbor-100">
                No trial periods, no feature paywalls, no surprise invoices.
            </p>
            <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ route('register') }}" class="btn w-full bg-white px-7 py-3.5 text-base font-semibold text-harbor-700 shadow-soft hover:bg-harbor-50 sm:w-auto">
                    Create your free account
                </a>
                <a href="{{ route('features') }}" class="btn w-full border border-white/30 px-7 py-3.5 text-base text-white hover:bg-white/10 sm:w-auto">
                    See what's included
                </a>
            </div>
        </div>
    </section>

@endcomponent
