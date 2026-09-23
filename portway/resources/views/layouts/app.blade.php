<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dashboard' }} · {{ config('app.brand.name') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Applies the saved theme before first paint to avoid a flash of the wrong theme. --}}
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
<body class="h-full bg-surface-50 dark:bg-surface-950">
    <div class="flex h-full">
        @include('layouts.partials.sidebar')

        <div class="flex min-w-0 flex-1 flex-col">
            @include('layouts.partials.topbar')

            <main class="flex-1 overflow-y-auto pb-20 md:pb-6">
                @if (session()->has('impersonator_id'))
                    <div class="flex items-center justify-between gap-3 bg-amber-500 px-4 py-2 text-sm text-white sm:px-6 lg:px-8">
                        <span class="flex items-center gap-2">
                            <x-heroicon-o-eye class="h-4 w-4" />
                            You are viewing Portway as <strong>{{ auth()->user()->name }}</strong>.
                        </span>
                        <form method="POST" action="{{ route('impersonate.stop') }}">
                            @csrf
                            <button type="submit" class="rounded-md bg-white/20 px-2.5 py-1 font-medium hover:bg-white/30">Stop impersonating</button>
                        </form>
                    </div>
                @endif

                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    @include('layouts.partials.announcements')

                    {{ $slot }}
                </div>
            </main>

            @include('layouts.partials.mobile-nav')
        </div>
    </div>

    @livewire('command-palette')
    @include('layouts.partials.toasts')

    @stack('scripts')
    @livewireScripts
</body>
</html>
