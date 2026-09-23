<div class="card">
    <h3 class="mb-3 font-semibold text-slate-800 dark:text-slate-100">DNS</h3>
    <p class="mb-3 text-sm text-slate-500 dark:text-slate-400">DNS records are managed per domain.</p>
    <ul class="divide-y divide-surface-100 dark:divide-white/5">
        @foreach ($site->domains as $domain)
            <li class="flex items-center justify-between py-3 text-sm">
                <span class="text-slate-700 dark:text-slate-200">{{ $domain->hostname }}</span>
                <a href="{{ route('domains.show', ['domain' => $domain, 'tab' => 'dns']) }}" wire:navigate class="btn-secondary !py-1 !px-3 text-xs">Manage DNS</a>
            </li>
        @endforeach
    </ul>
</div>
