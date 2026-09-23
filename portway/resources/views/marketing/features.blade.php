@component('layouts.guest', ['title' => 'Features'])

    {{-- Hero -------------------------------------------------------------- --}}
    <section class="relative isolate overflow-hidden">
        <div aria-hidden="true"
             class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[30rem] bg-gradient-to-b from-harbor-50 via-white to-white dark:from-harbor-500/10 dark:via-surface-950 dark:to-surface-950"></div>
        <div aria-hidden="true"
             class="pointer-events-none absolute -top-40 left-1/2 -z-10 h-72 w-[40rem] -translate-x-1/2 rounded-full bg-harbor-300/30 blur-3xl dark:bg-harbor-500/20"></div>

        <div class="mx-auto max-w-4xl px-4 pb-14 pt-16 text-center sm:px-6 sm:pb-20 sm:pt-24 lg:px-8">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-harbor-600 dark:text-harbor-400">Everything included</p>
            <h1 class="mt-4 text-4xl font-bold leading-[1.05] tracking-tight text-slate-900 dark:text-white sm:text-6xl">
                A full hosting stack, free.
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-slate-600 dark:text-slate-400 sm:text-xl">
                Every Portway account includes the same tools, whether you're launching a landing page, running a small
                application, or handing out desktop builds. Nothing here is a paid add-on.
            </p>
            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ route('register') }}" class="btn-primary w-full px-7 py-3.5 text-base shadow-lift sm:w-auto">
                    Start hosting free
                    <x-heroicon-o-arrow-right class="h-4 w-4" />
                </a>
                <a href="{{ route('apps.index') }}" class="btn-secondary w-full px-7 py-3.5 text-base sm:w-auto">
                    <x-heroicon-o-cube class="h-4 w-4" />
                    Browse published apps
                </a>
            </div>
        </div>
    </section>

    {{-- Capability groups --------------------------------------------------- --}}
    <section class="border-t border-surface-200 bg-surface-50 py-20 dark:border-white/10 dark:bg-white/[0.02] sm:py-24">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-2">
                @foreach ([
                    [
                        'group' => 'Hosting',
                        'icon' => 'globe-alt',
                        'summary' => 'Sites, domains, databases and files, all from one dashboard.',
                        'items' => [
                            'PHP and Node.js websites, side by side',
                            'Guided domain connection from any registrar',
                            'MySQL/MariaDB databases with a browser-based manager',
                            'A full file manager with a built-in code editor',
                        ],
                    ],
                    [
                        'group' => 'Developer tools',
                        'icon' => 'code-bracket-square',
                        'summary' => 'The parts you would otherwise SSH in for.',
                        'items' => [
                            'Git-based deployments with build logs',
                            'A sandboxed web terminal (composer, npm, artisan, wp-cli)',
                            'Cron jobs with common presets or custom expressions',
                            'Per-site environment variables and redirects',
                        ],
                    ],
                    [
                        'group' => 'App distribution',
                        'icon' => 'cube',
                        'summary' => 'Publish desktop builds and get a public download page.',
                        'items' => [
                            'Windows, macOS and Linux builds per application',
                            'Exactly one live build per platform, swapped by publishing a newer one',
                            'Version, size, architecture, minimum OS and release notes shown to visitors',
                            'SHA-256 checksums and a download count on every listing',
                        ],
                    ],
                    [
                        'group' => 'Security',
                        'icon' => 'shield-check',
                        'summary' => 'Defaults that assume someone will try.',
                        'items' => [
                            'Free, auto-renewing SSL certificates',
                            'Two-factor authentication with recovery codes',
                            'Tamper-resistant activity logging',
                            'Per-account and platform-wide IP blocking',
                        ],
                    ],
                    [
                        'group' => 'Reliability',
                        'icon' => 'archive-box',
                        'summary' => 'Know what you are using, and get it back when you need to.',
                        'items' => [
                            'On-demand backups of files and databases',
                            'One-click restores',
                            'Usage dashboards for storage and bandwidth',
                            'Support tickets answered by a real team',
                        ],
                    ],
                    [
                        'group' => 'Automation & API',
                        'icon' => 'command-line',
                        'summary' => 'Drive the panel from your own scripts.',
                        'items' => [
                            'A versioned REST API under /api/v1',
                            'Personal access tokens scoped to specific abilities',
                            'Abilities such as sites:read and deployments:write',
                            'Create and revoke tokens from Account Settings',
                        ],
                    ],
                ] as $section)
                    <div class="card flex h-full flex-col p-7 transition hover:shadow-lift">
                        <div class="flex items-start gap-4">
                            <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-harbor-50 text-harbor-600 dark:bg-harbor-500/10 dark:text-harbor-300">
                                <x-dynamic-component :component="'heroicon-o-'.$section['icon']" class="h-6 w-6" />
                            </span>
                            <div class="min-w-0">
                                <h2 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ $section['group'] }}</h2>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $section['summary'] }}</p>
                            </div>
                        </div>

                        <ul class="mt-6 space-y-3 border-t border-surface-200 pt-6 text-sm text-slate-600 dark:border-white/10 dark:text-slate-300">
                            @foreach ($section['items'] as $item)
                                <li class="flex items-start gap-2.5">
                                    <x-heroicon-o-check-circle class="mt-0.5 h-4 w-4 flex-shrink-0 text-harbor-500 dark:text-harbor-400" />
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- FAQ ------------------------------------------------------------------ --}}
    <section class="border-t border-surface-200 py-20 dark:border-white/10 sm:py-24">
        <div class="mx-auto grid max-w-6xl gap-12 px-4 sm:px-6 lg:grid-cols-3 lg:gap-16 lg:px-8">
            <div class="lg:col-span-1">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-harbor-600 dark:text-harbor-400">FAQ</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl">Common questions</h2>
                <p class="mt-4 text-slate-600 dark:text-slate-400">
                    Still unsure about something? Every account can open a support ticket from the dashboard.
                </p>
            </div>

            <div class="space-y-4 lg:col-span-2">
                @foreach ([
                    ['q' => 'Is this really free?', 'a' => 'Yes. Every account gets free hosting with generous limits, and there is no payment method required to sign up or to keep using Portway.'],
                    ['q' => 'What are the limits?', 'a' => 'By default, every account gets 10 GB of storage and a set of website, database, and backup limits. Platform administrators can adjust these defaults, and can raise limits for individual accounts.'],
                    ['q' => 'Can I use my own domain?', 'a' => 'Yes — connect a domain from any registrar by pointing its DNS at Portway. We\'ll guide you through it and issue a free SSL certificate automatically once it resolves.'],
                    ['q' => 'What can I build?', 'a' => 'Anything that runs on PHP or Node.js — from static sites and WordPress installs to custom applications you deploy from Git.'],
                    ['q' => 'How does publishing a desktop app work?', 'a' => 'Create an application in the panel and upload a build for each platform you support. The most recently published build for a platform is the live one, and it is what the public download page serves — older builds are archived rather than shown.'],
                    ['q' => 'What if I only ship for one platform?', 'a' => 'Then only that platform appears. A platform with no live build is left off the download page entirely, rather than shown as unavailable.'],
                ] as $faq)
                    <div class="card p-6 transition hover:shadow-lift">
                        <p class="font-semibold tracking-tight text-slate-900 dark:text-white">{{ $faq['q'] }}</p>
                        <p class="mt-2 text-sm leading-relaxed text-slate-500 dark:text-slate-400">{{ $faq['a'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Closing CTA ----------------------------------------------------------- --}}
    <section class="px-4 pb-20 sm:px-6 lg:px-8">
        <div class="relative isolate mx-auto max-w-6xl overflow-hidden rounded-xl2 bg-harbor-600 px-6 py-16 text-center shadow-lift sm:px-12 sm:py-20">
            <div aria-hidden="true"
                 class="pointer-events-none absolute -right-24 -top-24 -z-10 h-72 w-72 rounded-full bg-harbor-400/40 blur-3xl"></div>
            <div aria-hidden="true"
                 class="pointer-events-none absolute -bottom-32 -left-20 -z-10 h-72 w-72 rounded-full bg-harbor-800/50 blur-3xl"></div>

            <h2 class="mx-auto max-w-2xl text-3xl font-bold tracking-tight text-white sm:text-5xl">
                Every feature on this page, on day one.
            </h2>
            <p class="mx-auto mt-4 max-w-xl text-lg text-harbor-100">
                Nothing is gated, and nothing expires.
            </p>
            <a href="{{ route('register') }}" class="btn mt-9 bg-white px-7 py-3.5 text-base font-semibold text-harbor-700 shadow-soft hover:bg-harbor-50">
                Start hosting free
            </a>
        </div>
    </section>

@endcomponent
