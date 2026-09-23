<div class="space-y-6">
    @if (! $repository)
        <div class="card max-w-lg">
            <h3 class="mb-4 font-semibold text-slate-800 dark:text-slate-100">Connect a Git repository</h3>
            <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">
                Portway will clone the repository into this website's document root and let you deploy new commits on demand.
            </p>
            <div class="space-y-4">
                <div>
                    <label class="label">Repository URL</label>
                    <input type="text" wire:model="url" placeholder="https://github.com/you/project.git" class="input font-mono text-sm">
                    @error('url') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Branch</label>
                    <input type="text" wire:model="branch" placeholder="main" class="input font-mono text-sm">
                    @error('branch') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <button wire:click="connect" wire:loading.attr="disabled" class="btn-primary mt-5">
                <span wire:loading.remove wire:target="connect">Connect &amp; clone</span>
                <span wire:loading wire:target="connect">Cloning…</span>
            </button>
        </div>
    @else
        <div class="card max-w-lg">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">Repository</h3>
                <span class="badge-neutral capitalize">{{ $repository->provider }}</span>
            </div>

            <p class="mb-4 break-all font-mono text-sm text-slate-600 dark:text-slate-300">{{ $repository->url }}</p>

            <div class="space-y-4">
                <div>
                    <label class="label">Branch</label>
                    <input type="text" wire:model="editBranch" class="input font-mono text-sm">
                </div>
                <div>
                    <label class="label">Install command</label>
                    <input type="text" wire:model="installCommand" placeholder="composer install" class="input font-mono text-sm">
                </div>
                <div>
                    <label class="label">Build command</label>
                    <input type="text" wire:model="buildCommand" placeholder="npm run build" class="input font-mono text-sm">
                </div>
                <label class="flex items-center gap-2">
                    <input type="checkbox" wire:model="autoDeployOnPush" class="rounded border-surface-300 text-harbor-600">
                    <span class="text-sm text-slate-700 dark:text-slate-200">Auto-deploy on push (via webhook)</span>
                </label>
            </div>

            <div class="mt-5 flex flex-wrap gap-2">
                <button wire:click="deployNow" wire:loading.attr="disabled" class="btn-primary">Deploy now</button>
                <button wire:click="saveSettings" class="btn-secondary">Save settings</button>
                <button wire:click="confirmDisconnect" class="btn-ghost text-rose-600">Disconnect</button>
            </div>

            @if ($repository->last_deployed_at)
                <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">
                    Last deployed {{ $repository->last_deployed_at->diffForHumans() }}
                    @if ($repository->last_commit_sha)
                        &middot; <span class="font-mono">{{ substr($repository->last_commit_sha, 0, 7) }}</span>
                    @endif
                </p>
            @endif
        </div>

        <div class="card">
            <h3 class="mb-3 font-semibold text-slate-800 dark:text-slate-100">Deployment history</h3>

            @if ($deployments->isEmpty())
                <x-empty-state icon="rocket-launch" title="No deployments yet" description="Deploy the repository to see its history here." />
            @else
                <ul class="divide-y divide-surface-100 dark:divide-white/5">
                    @foreach ($deployments as $deployment)
                        <li class="flex items-center justify-between py-3 text-sm">
                            <div>
                                <span class="{{ match ($deployment->status) {
                                    'succeeded' => 'badge-success',
                                    'failed' => 'badge-danger',
                                    'running' => 'badge-info',
                                    default => 'badge-neutral',
                                } }}">{{ ucfirst($deployment->status) }}</span>
                                <span class="ml-2 text-slate-500 dark:text-slate-400">{{ $deployment->created_at->diffForHumans() }}</span>
                                @if ($deployment->commit_sha)
                                    <span class="ml-2 font-mono text-slate-400">{{ substr($deployment->commit_sha, 0, 7) }}</span>
                                @endif
                            </div>
                            <button wire:click="viewLog({{ $deployment->id }})" class="btn-ghost !py-1 !px-2 text-xs">View log</button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    <x-modal :show="$showDisconnectConfirm" wire:click.outside="$set('showDisconnectConfirm', false)">
        <h3 class="mb-2 font-semibold text-slate-800 dark:text-slate-100">Disconnect repository?</h3>
        <p class="mb-5 text-sm text-slate-500 dark:text-slate-400">
            This removes the Git connection and deployment history. Files already deployed to the website are not deleted.
        </p>
        <div class="flex justify-end gap-2">
            <button wire:click="$set('showDisconnectConfirm', false)" class="btn-secondary">Cancel</button>
            <button wire:click="disconnect" class="btn-danger">Disconnect</button>
        </div>
    </x-modal>

    <x-modal :show="(bool) $viewingDeployment" max-width="2xl" wire:click.outside="closeLog">
        @if ($viewingDeployment)
            <div class="mb-3 flex items-center justify-between">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">Deployment log &middot; {{ $viewingDeployment->created_at->format('M j, Y H:i') }}</h3>
                <button wire:click="closeLog" class="btn-ghost !p-1.5"><x-heroicon-o-x-mark class="h-5 w-5" /></button>
            </div>
            <pre class="max-h-96 overflow-auto rounded-lg bg-slate-900 p-4 text-xs text-slate-100">{{ $viewingDeployment->log ?: 'No output recorded.' }}</pre>
        @endif
    </x-modal>
</div>
