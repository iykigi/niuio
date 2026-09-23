@php
    $navGroups = [
        null => [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home'],
        ],
        'Hosting' => [
            ['label' => 'Websites', 'route' => 'sites.index', 'icon' => 'globe-alt'],
            ['label' => 'Domains', 'route' => 'domains.index', 'icon' => 'link'],
            ['label' => 'Databases', 'route' => 'databases.index', 'icon' => 'circle-stack'],
            ['label' => 'Files', 'route' => 'sites.index', 'params' => ['tool' => 'files'], 'icon' => 'folder'],
        ],
        'Distribution' => [
            ['label' => 'Applications', 'route' => 'applications.index', 'icon' => 'cube'],
        ],
        'Developer' => [
            ['label' => 'Terminal', 'route' => 'sites.index', 'params' => ['tool' => 'terminal'], 'icon' => 'command-line'],
            ['label' => 'Git', 'route' => 'sites.index', 'params' => ['tool' => 'git'], 'icon' => 'code-bracket-square'],
            ['label' => 'Cron Jobs', 'route' => 'sites.index', 'params' => ['tool' => 'cron'], 'icon' => 'clock'],
            ['label' => 'Logs', 'route' => 'sites.index', 'params' => ['tool' => 'logs'], 'icon' => 'document-text'],
        ],
        'Security' => [
            ['label' => 'SSL', 'route' => 'domains.index', 'icon' => 'lock-closed'],
            ['label' => 'Backups', 'route' => 'sites.index', 'params' => ['tool' => 'backups'], 'icon' => 'archive-box'],
            ['label' => 'Security', 'route' => 'security', 'icon' => 'shield-check'],
        ],
        'Account' => [
            ['label' => 'Usage', 'route' => 'usage', 'icon' => 'chart-bar'],
            ['label' => 'Settings', 'route' => 'settings', 'icon' => 'cog-6-tooth'],
            ['label' => 'Support', 'route' => 'support.index', 'icon' => 'lifebuoy'],
        ],
    ];
@endphp

<aside class="hidden w-64 flex-shrink-0 flex-col border-r border-surface-200 bg-white dark:border-white/10 dark:bg-surface-900 md:flex">
    <div class="flex h-16 items-center gap-2 px-5">
        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-harbor-600 text-sm font-bold text-white">P</span>
        <span class="text-lg font-semibold text-slate-900 dark:text-white">{{ config('app.brand.name') }}</span>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4">
        @foreach ($navGroups as $group => $items)
            <div>
                @if ($group)
                    <p class="mb-1.5 px-3 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ $group }}</p>
                @endif
                <div class="space-y-1">
                    @foreach ($items as $item)
                        @php $active = request()->routeIs($item['route']) && ! request()->routeIs('admin.*'); @endphp
                        <a href="{{ route($item['route'], $item['params'] ?? []) }}"
                           class="nav-link {{ $active ? 'nav-link-active' : '' }}">
                            <x-dynamic-component :component="'heroicon-o-'.$item['icon']" class="h-5 w-5 flex-shrink-0" />
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach

        @auth
            @if (auth()->user()->isStaff())
                <div>
                    <p class="mb-1.5 px-3 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Admin</p>
                    <a href="{{ route('admin.overview') }}" class="nav-link {{ request()->routeIs('admin.*') ? 'nav-link-active' : '' }}">
                        <x-heroicon-o-adjustments-horizontal class="h-5 w-5 flex-shrink-0" />
                        Admin Panel
                    </a>
                </div>
            @endif
        @endauth
    </nav>

    <div class="border-t border-surface-200 p-3 dark:border-white/10">
        <button
            type="button"
            title="Search (⌘K)"
            onclick="window.dispatchEvent(new CustomEvent('command-palette:toggle'))"
            class="nav-link w-full justify-between"
        >
            <span class="flex items-center gap-3"><x-heroicon-o-magnifying-glass class="h-5 w-5" /> Quick search</span>
            <kbd class="rounded border border-surface-200 bg-surface-50 px-1.5 py-0.5 text-xs text-slate-400 dark:border-white/10 dark:bg-white/5">⌘K</kbd>
        </button>
    </div>
</aside>
