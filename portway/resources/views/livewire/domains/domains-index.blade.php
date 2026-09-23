<div>
    <div class="mb-6 flex items-center justify-between">
        <p class="text-sm text-slate-500 dark:text-slate-400">Connect domains from any registrar — Cloudflare, Namecheap, GoDaddy, Porkbun and more.</p>
        <button wire:click="$set('showAddModal', true)" class="btn-primary"><x-heroicon-o-plus class="h-4 w-4" /> Add Domain</button>
    </div>

    @if ($domains->isEmpty())
        <x-empty-state icon="link" title="No domains connected" description="Add a domain you already own, or use a free Portway subdomain for now.">
            <x-slot:action>
                <button wire:click="$set('showAddModal', true)" class="btn-primary">Add Domain</button>
            </x-slot:action>
        </x-empty-state>
    @else
        <div class="card overflow-hidden !p-0">
            <table class="w-full text-sm">
                <thead class="border-b border-surface-200 bg-surface-50 text-left text-xs uppercase tracking-wide text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Domain</th>
                        <th class="px-4 py-3">Website</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">SSL</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-100 dark:divide-white/5">
                    @foreach ($domains as $domain)
                        <tr wire:key="domain-{{ $domain->id }}">
                            <td class="px-4 py-3 font-medium text-slate-800 dark:text-slate-100">{{ $domain->hostname }}</td>
                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $domain->site->name }}</td>
                            <td class="px-4 py-3"><span class="{{ $domain->status->badgeClass() }}">{{ $domain->status->label() }}</span></td>
                            <td class="px-4 py-3">
                                @if ($domain->sslCertificate)
                                    <span class="{{ $domain->sslCertificate->status->badgeClass() }}">{{ $domain->sslCertificate->status->label() }}</span>
                                @else
                                    <span class="badge-neutral">None</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if (! $domain->isTemporary())
                                    <button wire:click="checkDns({{ $domain->id }})" class="btn-secondary !px-3 !py-1.5 text-xs">Check DNS</button>
                                @endif
                                <a href="{{ route('domains.show', $domain) }}" wire:navigate class="btn-secondary !px-3 !py-1.5 text-xs">Manage</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <x-modal :show="$showAddModal" wire:click.outside="$set('showAddModal', false)" max-width="lg">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Add a domain</h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Enter a domain you already own from any registrar.</p>

        <div class="mt-4">
            <label class="label">Domain</label>
            <input type="text" wire:model="hostname" class="input" placeholder="example.com">
            @error('hostname') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="mt-4">
            <label class="label">Attach to website</label>
            <select wire:model="siteId" class="input">
                @foreach ($sites as $site)
                    <option value="{{ $site->id }}">{{ $site->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mt-5 flex justify-end gap-2">
            <button wire:click="$set('showAddModal', false)" class="btn-secondary">Cancel</button>
            <button wire:click="addDomain" class="btn-primary">Add Domain</button>
        </div>
    </x-modal>
</div>
