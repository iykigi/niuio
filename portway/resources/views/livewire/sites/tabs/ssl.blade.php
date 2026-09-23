<div class="card">
    <h3 class="mb-3 font-semibold text-slate-800 dark:text-slate-100">SSL certificates</h3>
    <ul class="divide-y divide-surface-100 dark:divide-white/5">
        @foreach ($site->domains as $domain)
            <li class="flex items-center justify-between py-3 text-sm">
                <span class="text-slate-700 dark:text-slate-200">{{ $domain->hostname }}</span>
                <div class="flex items-center gap-2">
                    @if ($domain->sslCertificate)
                        <span class="{{ $domain->sslCertificate->status->badgeClass() }}">{{ $domain->sslCertificate->status->label() }}</span>
                    @else
                        <span class="badge-neutral">None</span>
                    @endif
                    <a href="{{ route('domains.show', ['domain' => $domain, 'tab' => 'ssl']) }}" wire:navigate class="btn-ghost !p-1.5"><x-heroicon-o-arrow-right class="h-4 w-4" /></a>
                </div>
            </li>
        @endforeach
    </ul>
</div>
