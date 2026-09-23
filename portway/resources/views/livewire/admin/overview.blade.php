<div>
    <h1 class="mb-1 text-xl font-semibold text-slate-900 dark:text-white">Admin panel</h1>
    <p class="mb-6 text-sm text-slate-500 dark:text-slate-400">Platform-wide overview across every account and hosting node.</p>

    @include('admin.partials.nav')

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card label="Users" :value="number_format($totalUsers)" icon="users" :trend="$newUsersThisWeek.' new this week'" />
        <x-stat-card label="Websites" :value="number_format($totalSites)" icon="globe-alt" :trend="number_format($activeSites).' active'" />
        <x-stat-card label="Domains" :value="number_format($totalDomains)" icon="link" />
        <x-stat-card label="Databases" :value="number_format($totalDatabases)" icon="circle-stack" />
        <x-stat-card label="Completed backups" :value="number_format($totalBackups)" icon="archive-box" />
        <x-stat-card label="Hosting nodes online" :value="$onlineServers.' / '.$totalServers" icon="server" />
        <x-stat-card
            label="Open support tickets"
            :value="number_format($openTickets)"
            icon="lifebuoy"
            :trend="$unassignedTickets > 0 ? $unassignedTickets.' unassigned' : null"
            :trendUp="false"
        />
        <x-stat-card label="Suspended accounts" :value="number_format($suspendedUsers)" icon="no-symbol" :trendUp="false" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="card">
            <h3 class="mb-3 font-semibold text-slate-800 dark:text-slate-100">Newest accounts</h3>
            <ul class="divide-y divide-surface-100 dark:divide-white/5">
                @foreach ($recentUsers as $user)
                    <li class="flex items-center justify-between py-2.5 text-sm">
                        <a href="{{ route('admin.users.show', $user) }}" wire:navigate class="font-medium text-slate-700 hover:text-harbor-700 dark:text-slate-200">{{ $user->name }}</a>
                        <span class="text-slate-400">{{ $user->created_at->diffForHumans() }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="card">
            <h3 class="mb-3 font-semibold text-slate-800 dark:text-slate-100">Recent activity</h3>
            <ul class="max-h-72 divide-y divide-surface-100 overflow-y-auto dark:divide-white/5">
                @forelse ($recentActivity as $entry)
                    <li class="py-2.5 text-sm">
                        <span class="text-slate-700 dark:text-slate-200">{{ $entry->description ?: $entry->action }}</span>
                        @if ($entry->user)
                            <span class="text-slate-400"> &middot; {{ $entry->user->name }}</span>
                        @endif
                        <span class="block text-xs text-slate-400">{{ $entry->created_at->diffForHumans() }}</span>
                    </li>
                @empty
                    <li class="py-4 text-center text-sm text-slate-400">No activity recorded yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
