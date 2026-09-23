@php
    $adminNavItems = array_filter([
        ['label' => 'Overview', 'route' => 'admin.overview', 'show' => true],
        ['label' => 'Users', 'route' => 'admin.users.index', 'show' => auth()->user()->can('users.view')],
        ['label' => 'Servers', 'route' => 'admin.servers.index', 'show' => auth()->user()->can('servers.view')],
        ['label' => 'Release archive', 'route' => 'admin.releases.archive', 'show' => auth()->user()->can('releases.view')],
        ['label' => 'Support', 'route' => 'admin.support.index', 'show' => auth()->user()->can('support.view')],
        ['label' => 'Announcements', 'route' => 'admin.announcements', 'show' => auth()->user()->can('announcements.view')],
        ['label' => 'Roles', 'route' => 'admin.roles', 'show' => auth()->user()->can('roles.view')],
        ['label' => 'Settings', 'route' => 'admin.settings', 'show' => auth()->user()->can('settings.view')],
    ], fn ($item) => $item['show']);
@endphp

<div class="mb-6 overflow-x-auto">
    <div class="flex gap-1 border-b border-surface-200 dark:border-white/10">
        @foreach ($adminNavItems as $item)
            <a
                href="{{ route($item['route']) }}"
                wire:navigate
                class="whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium {{ request()->routeIs($item['route'].'*') ? 'border-harbor-600 text-harbor-700 dark:border-harbor-400 dark:text-harbor-300' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}"
            >
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>
</div>
