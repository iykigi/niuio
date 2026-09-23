<div class="card">
    <div class="mb-3 flex items-center justify-between">
        <h3 class="font-semibold text-slate-800 dark:text-slate-100">Domains for this website</h3>
        <a href="{{ route('domains.index') }}" wire:navigate class="btn-secondary !py-1.5 text-sm">Manage all domains</a>
    </div>
    <ul class="divide-y divide-surface-100 dark:divide-white/5">
        @foreach ($site->domains as $domain)
            <li class="flex items-center justify-between py-3 text-sm">
                <span class="text-slate-700 dark:text-slate-200">{{ $domain->hostname }}</span>
                <div class="flex items-center gap-2">
                    <span class="{{ $domain->status->badgeClass() }}">{{ $domain->status->label() }}</span>
                    @unless ($domain->isTemporary())
                        <a href="{{ route('domains.show', $domain) }}" wire:navigate class="btn-ghost !p-1.5"><x-heroicon-o-arrow-right class="h-4 w-4" /></a>
                    @endunless
                </div>
            </li>
        @endforeach
    </ul>
</div>
