<nav class="fixed inset-x-0 bottom-0 z-30 flex border-t border-surface-200 bg-white/95 backdrop-blur dark:border-white/10 dark:bg-surface-900/95 md:hidden" x-data="{ drawer: false }">
    <a href="{{ route('dashboard') }}" class="flex flex-1 flex-col items-center gap-0.5 py-2.5 text-xs {{ request()->routeIs('dashboard') ? 'text-harbor-600 dark:text-harbor-400' : 'text-slate-500 dark:text-slate-400' }}">
        <x-heroicon-o-home class="h-5 w-5" /> Home
    </a>
    <a href="{{ route('sites.index') }}" class="flex flex-1 flex-col items-center gap-0.5 py-2.5 text-xs {{ request()->routeIs('sites.*') ? 'text-harbor-600 dark:text-harbor-400' : 'text-slate-500 dark:text-slate-400' }}">
        <x-heroicon-o-globe-alt class="h-5 w-5" /> Sites
    </a>
    <a href="{{ route('databases.index') }}" class="flex flex-1 flex-col items-center gap-0.5 py-2.5 text-xs {{ request()->routeIs('databases.*') ? 'text-harbor-600 dark:text-harbor-400' : 'text-slate-500 dark:text-slate-400' }}">
        <x-heroicon-o-circle-stack class="h-5 w-5" /> Databases
    </a>
    <button type="button" @click="drawer = true" class="flex flex-1 flex-col items-center gap-0.5 py-2.5 text-xs text-slate-500 dark:text-slate-400">
        <x-heroicon-o-bars-3 class="h-5 w-5" /> More
    </button>

    <div x-show="drawer" x-cloak class="fixed inset-0 z-40" @keydown.escape.window="drawer = false">
        <div class="absolute inset-0 bg-black/40" @click="drawer = false"></div>
        <div class="absolute inset-x-0 bottom-0 rounded-t-2xl bg-white p-4 shadow-lift dark:bg-surface-900" x-show="drawer" x-transition>
            <div class="mx-auto mb-3 h-1.5 w-10 rounded-full bg-surface-200 dark:bg-white/10"></div>
            <div class="grid grid-cols-3 gap-3">
                @foreach ([
                    ['label' => 'Domains', 'route' => 'domains.index', 'icon' => 'link'],
                    ['label' => 'Backups', 'route' => 'sites.index', 'icon' => 'archive-box'],
                    ['label' => 'Security', 'route' => 'security', 'icon' => 'shield-check'],
                    ['label' => 'Usage', 'route' => 'usage', 'icon' => 'chart-bar'],
                    ['label' => 'Settings', 'route' => 'settings', 'icon' => 'cog-6-tooth'],
                    ['label' => 'Support', 'route' => 'support.index', 'icon' => 'lifebuoy'],
                ] as $item)
                    <a href="{{ route($item['route']) }}" class="flex flex-col items-center gap-1.5 rounded-xl border border-surface-200 p-3 text-xs text-slate-600 dark:border-white/10 dark:text-slate-300">
                        <x-dynamic-component :component="'heroicon-o-'.$item['icon']" class="h-5 w-5" />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</nav>
