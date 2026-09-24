<div>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">{{ $domain->hostname }}</h2>
                <span class="{{ $domain->status->badgeClass() }}">{{ $domain->status->label() }}</span>
            </div>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Connected to {{ $domain->site->name }}</p>
        </div>
        <div class="flex gap-2">
            <button wire:click="checkDns" class="btn-secondary"><x-heroicon-o-arrow-path class="h-4 w-4" /> Check DNS</button>
            @if ($domain->type !== 'temporary')
                <button wire:click="delete" wire:confirm="Disconnect this domain?" class="btn-danger">Disconnect</button>
            @endif
        </div>
    </div>

    <div class="mb-6 flex gap-1 border-b border-surface-200 dark:border-white/10">
        @foreach (['dns' => 'DNS', 'ssl' => 'SSL', 'settings' => 'Settings'] as $key => $label)
            <button wire:click="setTab(@js($key))" class="border-b-2 px-3 py-2.5 text-sm font-medium {{ $tab === $key ? 'border-harbor-600 text-harbor-700 dark:border-harbor-400 dark:text-harbor-300' : 'border-transparent text-slate-500 dark:text-slate-400' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    @if ($tab === 'dns')
        @unless ($domain->isTemporary())
            <div class="card mb-6">
                <h3 class="mb-3 font-semibold text-slate-800 dark:text-slate-100">Setup instructions</h3>
                <p class="mb-3 text-sm text-slate-500 dark:text-slate-400">Add these records at your domain registrar's DNS panel:</p>
                <div class="overflow-hidden rounded-lg border border-surface-200 dark:border-white/10">
                    <table class="w-full text-sm">
                        <thead class="bg-surface-50 text-left text-xs uppercase text-slate-500 dark:bg-white/5 dark:text-slate-400">
                            <tr><th class="px-3 py-2">Type</th><th class="px-3 py-2">Name</th><th class="px-3 py-2">Value</th></tr>
                        </thead>
                        <tbody class="divide-y divide-surface-100 font-mono text-xs dark:divide-white/5">
                            @foreach ($expectedRecords as $record)
                                <tr><td class="px-3 py-2">{{ $record['type'] }}</td><td class="px-3 py-2">{{ $record['name'] }}</td><td class="px-3 py-2">{{ $record['value'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($domain->last_dns_check_result)
                    <p class="mt-3 text-xs text-slate-400">Last checked {{ $domain->last_dns_check_at?->diffForHumans() }} ·
                        detected A: {{ implode(', ', $domain->last_dns_check_result['a'] ?? []) ?: 'none yet' }}
                    </p>
                @endif
            </div>
        @endunless

        <div class="card">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">DNS records</h3>
                <button wire:click="$set('showAddRecord', true)" class="btn-secondary !py-1.5 text-sm"><x-heroicon-o-plus class="h-4 w-4" /> Add record</button>
            </div>
            <div class="overflow-hidden rounded-lg border border-surface-200 dark:border-white/10">
                <table class="w-full text-sm">
                    <thead class="bg-surface-50 text-left text-xs uppercase text-slate-500 dark:bg-white/5 dark:text-slate-400">
                        <tr><th class="px-3 py-2">Type</th><th class="px-3 py-2">Name</th><th class="px-3 py-2">Content</th><th class="px-3 py-2">TTL</th><th class="px-3 py-2">Status</th><th></th></tr>
                    </thead>
                    <tbody class="divide-y divide-surface-100 dark:divide-white/5">
                        @forelse ($dnsRecords as $record)
                            <tr wire:key="record-{{ $record->id }}">
                                <td class="px-3 py-2 font-mono text-xs">{{ $record->type }}</td>
                                <td class="px-3 py-2">{{ $record->name }}</td>
                                <td class="px-3 py-2 font-mono text-xs">{{ $record->displayContent() }}</td>
                                <td class="px-3 py-2">{{ $record->ttl }}</td>
                                <td class="px-3 py-2"><span class="badge-neutral">{{ ucfirst($record->status) }}</span></td>
                                <td class="px-3 py-2 text-right">
                                    <button wire:click="deleteRecord({{ $record->id }})" class="btn-ghost !p-1.5 text-rose-600"><x-heroicon-o-trash class="h-4 w-4" /></button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-6 text-center text-slate-400">No custom DNS records yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($tab === 'ssl')
        <div class="card">
            <h3 class="mb-3 font-semibold text-slate-800 dark:text-slate-100">SSL Certificate</h3>
            @if ($domain->sslCertificate)
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-slate-400">Status</dt><dd><span class="{{ $domain->sslCertificate->status->badgeClass() }}">{{ $domain->sslCertificate->status->label() }}</span></dd></div>
                    <div><dt class="text-slate-400">Issuer</dt><dd>{{ $domain->sslCertificate->issuer ?? '—' }}</dd></div>
                    <div><dt class="text-slate-400">Issued</dt><dd>{{ $domain->sslCertificate->issued_at?->toFormattedDateString() ?? '—' }}</dd></div>
                    <div><dt class="text-slate-400">Expires</dt><dd>{{ $domain->sslCertificate->expires_at?->toFormattedDateString() ?? '—' }}</dd></div>
                </dl>
            @else
                <p class="text-sm text-slate-500 dark:text-slate-400">No SSL certificate yet.</p>
            @endif
            <button wire:click="issueSsl" class="btn-primary mt-4">
                <x-heroicon-o-lock-closed class="h-4 w-4" /> {{ $domain->sslCertificate ? 'Reissue certificate' : 'Enable SSL' }}
            </button>
        </div>
    @endif

    @if ($tab === 'settings')
        <div class="card">
            <h3 class="mb-3 font-semibold text-slate-800 dark:text-slate-100">Settings</h3>
            <label class="flex items-center justify-between rounded-lg border border-surface-200 p-3 dark:border-white/10">
                <span>
                    <span class="block text-sm font-medium text-slate-800 dark:text-slate-100">Force HTTPS</span>
                    <span class="block text-xs text-slate-500 dark:text-slate-400">Automatically redirect HTTP traffic to HTTPS.</span>
                </span>
                <input type="checkbox" wire:click="toggleForceHttps" @checked($domain->force_https) class="rounded border-surface-300 text-harbor-600">
            </label>
        </div>
    @endif

    <x-modal :show="$showAddRecord" wire:click.outside="$set('showAddRecord', false)">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Add DNS record</h3>
        <div class="mt-4 space-y-3">
            <div>
                <label class="label">Type</label>
                <select wire:model="recordType" class="input">
                    @foreach (['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'SRV', 'CAA'] as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Name</label>
                <input type="text" wire:model="recordName" class="input" placeholder="@ or www">
            </div>
            <div>
                <label class="label">Content</label>
                <input type="text" wire:model="recordContent" class="input">
                @error('recordContent') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            @if ($recordType === 'MX')
                <div>
                    <label class="label">Priority</label>
                    <input type="number" wire:model="recordPriority" class="input">
                </div>
            @endif
            <div>
                <label class="label">TTL (seconds)</label>
                <input type="number" wire:model="recordTtl" class="input">
            </div>
        </div>
        <div class="mt-5 flex justify-end gap-2">
            <button wire:click="$set('showAddRecord', false)" class="btn-secondary">Cancel</button>
            <button wire:click="addRecord" class="btn-primary">Add record</button>
        </div>
    </x-modal>
</div>
