<header class="flex h-16 flex-shrink-0 items-center justify-between border-b border-surface-200 bg-white px-4 dark:border-white/10 dark:bg-surface-900 sm:px-6">
    <div class="flex items-center gap-3">
        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-harbor-600 text-xs font-bold text-white md:hidden">P</span>
        <h1 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $title ?? 'Dashboard' }}</h1>
    </div>

    <div class="flex items-center gap-2">
        <button
            type="button"
            onclick="window.dispatchEvent(new CustomEvent('command-palette:toggle'))"
            class="hidden items-center gap-2 rounded-lg border border-surface-200 px-3 py-1.5 text-sm text-slate-500 hover:bg-surface-50 dark:border-white/10 dark:text-slate-400 dark:hover:bg-white/5 sm:flex"
        >
            <x-heroicon-o-magnifying-glass class="h-4 w-4" />
            Search…
            <kbd class="rounded border border-surface-200 bg-surface-50 px-1 text-xs dark:border-white/10 dark:bg-white/5">⌘K</kbd>
        </button>

        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" @click.outside="open = false" type="button" class="btn-ghost !px-2" title="Toggle theme">
                <x-heroicon-o-sun class="h-5 w-5 dark:hidden" />
                <x-heroicon-o-moon class="hidden h-5 w-5 dark:block" />
            </button>
            <div x-show="open" x-cloak class="absolute right-0 z-20 mt-2 w-36 rounded-lg border border-surface-200 bg-white p-1 shadow-lift dark:border-white/10 dark:bg-surface-800">
                <button type="button" class="nav-link w-full !px-2 !py-1.5 text-sm" onclick="Portway.theme.set('light')">Light</button>
                <button type="button" class="nav-link w-full !px-2 !py-1.5 text-sm" onclick="Portway.theme.set('dark')">Dark</button>
                <button type="button" class="nav-link w-full !px-2 !py-1.5 text-sm" onclick="Portway.theme.set('system')">System</button>
            </div>
        </div>

        @auth
            <livewire:notifications-bell />

            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" @click.outside="open = false" type="button" class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-surface-50 dark:hover:bg-white/5">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-harbor-100 text-xs font-semibold text-harbor-700 dark:bg-harbor-500/20 dark:text-harbor-300">
                        {{ Str::of(auth()->user()->name)->substr(0, 1)->upper() }}
                    </span>
                </button>
                <div x-show="open" x-cloak class="absolute right-0 z-20 mt-2 w-48 rounded-lg border border-surface-200 bg-white p-1 shadow-lift dark:border-white/10 dark:bg-surface-800">
                    <p class="truncate px-3 py-2 text-sm font-medium text-slate-700 dark:text-slate-200">{{ auth()->user()->name }}</p>
                    <a href="{{ route('settings') }}" class="nav-link !px-3 !py-2 text-sm">Account settings</a>
                    <a href="{{ route('support.index') }}" class="nav-link !px-3 !py-2 text-sm">Support</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="nav-link w-full !px-3 !py-2 text-left text-sm text-rose-600 dark:text-rose-400">Log out</button>
                    </form>
                </div>
            </div>
        @endauth
    </div>
</header>
