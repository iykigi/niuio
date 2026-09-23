<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.brand.name') }} · {{ config('app.brand.name') }}</title>
    <meta name="description" content="{{ config('app.brand.tagline') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        (function () {
            try {
                var pref = localStorage.getItem('portway-theme') || 'system';
                var isDark = pref === 'dark' || (pref === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', isDark);
            } catch (e) {}
        })();
    </script>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-full flex-col bg-white dark:bg-surface-950">

    {{-- Sticky header ---------------------------------------------------- --}}
    <header class="sticky top-0 z-40 border-b border-surface-200/70 bg-white/80 backdrop-blur-md dark:border-white/10 dark:bg-surface-950/80">
        <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3.5 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex flex-shrink-0 items-center gap-2.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-harbor-600 text-sm font-bold text-white shadow-soft">P</span>
                <span class="text-lg font-semibold tracking-tight text-slate-900 dark:text-white">{{ config('app.brand.name') }}</span>
            </a>

            <nav class="hidden flex-1 items-center gap-1 md:flex">
                <a href="{{ route('features') }}"
                   class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-surface-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-white/5 dark:hover:text-white">
                    Features
                </a>
                <a href="{{ route('apps.index') }}"
                   class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-surface-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-white/5 dark:hover:text-white">
                    Apps
                </a>
            </nav>

            <div class="ml-auto flex items-center gap-2 md:ml-0">
                <button
                    type="button"
                    aria-label="Toggle dark mode"
                    onclick="window.Portway && window.Portway.theme.set(document.documentElement.classList.contains('dark') ? 'light' : 'dark')"
                    class="btn-ghost !px-2"
                >
                    <x-heroicon-o-sun class="h-5 w-5 dark:hidden" />
                    <x-heroicon-o-moon class="hidden h-5 w-5 dark:block" />
                </button>

                @auth
                    <a href="{{ route('dashboard') }}" class="btn-primary hidden sm:inline-flex">Go to dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn-ghost hidden sm:inline-flex">Log in</a>
                    <a href="{{ route('register') }}" class="btn-primary hidden sm:inline-flex">Start free</a>
                @endauth

                {{-- Mobile menu: plain <details>, so it needs no extra JS. --}}
                <details class="group relative md:hidden">
                    <summary class="btn-ghost !px-2 cursor-pointer list-none [&::-webkit-details-marker]:hidden">
                        <x-heroicon-o-bars-3 class="h-5 w-5 group-open:hidden" />
                        <x-heroicon-o-x-mark class="hidden h-5 w-5 group-open:block" />
                    </summary>
                    <div class="absolute right-0 z-50 mt-3 w-56 rounded-xl2 border border-surface-200 bg-white p-2 shadow-lift animate-slide-up dark:border-white/10 dark:bg-surface-900">
                        <a href="{{ route('features') }}" class="nav-link">
                            <x-heroicon-o-squares-2x2 class="h-4 w-4" /> Features
                        </a>
                        <a href="{{ route('apps.index') }}" class="nav-link">
                            <x-heroicon-o-cube class="h-4 w-4" /> Apps
                        </a>
                        <div class="my-2 border-t border-surface-200 dark:border-white/10"></div>
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn-primary w-full">Go to dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="nav-link">
                                <x-heroicon-o-arrow-right class="h-4 w-4" /> Log in
                            </a>
                            <a href="{{ route('register') }}" class="btn-primary mt-2 w-full">Start free</a>
                        @endauth
                    </div>
                </details>
            </div>
        </div>
    </header>

    <main class="flex-1">
        {{ $slot }}
    </main>

    {{-- Footer ----------------------------------------------------------- --}}
    <footer class="border-t border-surface-200 bg-surface-50 dark:border-white/10 dark:bg-white/[0.02]">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-harbor-600 text-sm font-bold text-white">P</span>
                        <span class="text-lg font-semibold tracking-tight text-slate-900 dark:text-white">{{ config('app.brand.name') }}</span>
                    </a>
                    <p class="mt-4 max-w-sm text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                        {{ config('app.brand.tagline') }} Free web hosting with the developer tools you'd expect from a
                        paid host — plus a public download page for the desktop apps you build.
                    </p>
                </div>

                <div>
                    <h2 class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Product</h2>
                    <ul class="mt-4 space-y-3 text-sm">
                        <li><a href="{{ route('features') }}" class="text-slate-600 transition hover:text-harbor-600 dark:text-slate-300 dark:hover:text-harbor-400">Features</a></li>
                        <li><a href="{{ route('apps.index') }}" class="text-slate-600 transition hover:text-harbor-600 dark:text-slate-300 dark:hover:text-harbor-400">App directory</a></li>
                        <li><a href="{{ route('home') }}" class="text-slate-600 transition hover:text-harbor-600 dark:text-slate-300 dark:hover:text-harbor-400">Overview</a></li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Account</h2>
                    <ul class="mt-4 space-y-3 text-sm">
                        @auth
                            <li><a href="{{ route('dashboard') }}" class="text-slate-600 transition hover:text-harbor-600 dark:text-slate-300 dark:hover:text-harbor-400">Dashboard</a></li>
                        @else
                            <li><a href="{{ route('login') }}" class="text-slate-600 transition hover:text-harbor-600 dark:text-slate-300 dark:hover:text-harbor-400">Log in</a></li>
                            <li><a href="{{ route('register') }}" class="text-slate-600 transition hover:text-harbor-600 dark:text-slate-300 dark:hover:text-harbor-400">Create a free account</a></li>
                        @endauth
                    </ul>
                </div>
            </div>

            <div class="mt-12 flex flex-col items-center justify-between gap-3 border-t border-surface-200 pt-6 text-sm text-slate-500 dark:border-white/10 dark:text-slate-400 sm:flex-row">
                <p>&copy; {{ now()->year }} {{ config('app.brand.name') }}.</p>
                <p>Free hosting, no credit card required.</p>
            </div>
        </div>
    </footer>

    @include('layouts.partials.toasts')
    @livewireScripts
</body>
</html>
