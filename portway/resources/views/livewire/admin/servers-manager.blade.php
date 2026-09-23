<div>
    <h1 class="mb-1 text-xl font-semibold text-slate-900 dark:text-white">Admin panel</h1>
    <p class="mb-6 text-sm text-slate-500 dark:text-slate-400">Hosting nodes backing the platform's control plane.</p>

    @include('admin.partials.nav')

    <div class="mb-4 flex justify-end">
        <button wire:click="openCreate" class="btn-primary">Add hosting node</button>
    </div>

    @if ($servers->isEmpty())
        <x-empty-state icon="server" title="No hosting nodes yet" description="Running on the local driver for now — add a real node here once you're ready to host on production Linux servers." />
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($servers as $server)
                <a href="{{ route('admin.servers.show', $server) }}" wire:navigate class="card block hover:border-harbor-300 dark:hover:border-harbor-500/40">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="font-semibold text-slate-800 dark:text-slate-100">{{ $server->name }}</span>
                        @php $health = $server->latestMetric?->healthStatus(); @endphp
                        <span class="{{ $health?->badgeClass() ?? 'badge-neutral' }}">{{ $health?->label() ?? ucfirst($server->status) }}</span>
                    </div>
                    <p class="font-mono text-xs text-slate-400">{{ $server->hostname }} &middot; {{ $server->ip_address }}</p>
                    <div class="mt-3 flex items-center justify-between text-sm text-slate-500 dark:text-slate-400">
                        <span class="capitalize">{{ $server->role }} &middot; {{ $server->web_server }}</span>
                        <span>{{ $server->current_sites }} / {{ $server->max_sites }} sites</span>
                    </div>
                    @if ($server->latestMetric)
                        <div class="mt-3 grid grid-cols-3 gap-2 text-xs text-slate-400">
                            <span>CPU {{ $server->latestMetric->cpu_percent }}%</span>
                            <span>Mem {{ $server->latestMetric->memory_percent }}%</span>
                            <span>Disk {{ $server->latestMetric->disk_percent }}%</span>
                        </div>
                    @endif
                </a>
            @endforeach
        </div>
    @endif

    <x-modal :show="$showCreate" max-width="lg" wire:click.outside="$set('showCreate', false)">
        <h3 class="mb-4 font-semibold text-slate-800 dark:text-slate-100">Add a hosting node</h3>
        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="label">Name</label>
                <input type="text" wire:model="name" placeholder="fra-web-01" class="input">
                @error('name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-2">
                <label class="label">Hostname</label>
                <input type="text" wire:model="hostname" placeholder="fra-web-01.portway.internal" class="input font-mono text-sm">
                @error('hostname') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">IP address</label>
                <input type="text" wire:model="ipAddress" class="input font-mono text-sm">
                @error('ipAddress') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Region</label>
                <input type="text" wire:model="region" placeholder="eu-central" class="input">
            </div>
            <div>
                <label class="label">Role</label>
                <select wire:model="role" class="input">
                    <option value="combined">Combined (web + db)</option>
                    <option value="web">Web only</option>
                    <option value="database">Database only</option>
                    <option value="backup">Backup storage</option>
                </select>
            </div>
            <div>
                <label class="label">Web server</label>
                <select wire:model="webServer" class="input">
                    <option value="nginx">Nginx</option>
                    <option value="apache">Apache</option>
                </select>
            </div>
            <div>
                <label class="label">Max websites</label>
                <input type="number" wire:model="maxSites" class="input">
            </div>
            <div>
                <label class="label">SSH user</label>
                <input type="text" wire:model="sshUser" class="input font-mono text-sm">
            </div>
            <div>
                <label class="label">SSH port</label>
                <input type="number" wire:model="sshPort" class="input">
            </div>
            <div class="col-span-2">
                <label class="label">SSH private key (optional — required for the "ssh" provisioner driver)</label>
                <textarea wire:model="sshPrivateKey" rows="3" class="input font-mono text-xs" placeholder="-----BEGIN OPENSSH PRIVATE KEY-----"></textarea>
                <p class="mt-1 text-xs text-slate-400">Encrypted at rest. Never shown again after saving.</p>
            </div>
        </div>
        <div class="mt-5 flex justify-end gap-2">
            <button wire:click="$set('showCreate', false)" class="btn-secondary">Cancel</button>
            <button wire:click="create" class="btn-primary">Add node</button>
        </div>
    </x-modal>
</div>
