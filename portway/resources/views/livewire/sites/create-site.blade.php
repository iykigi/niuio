<div class="mx-auto max-w-2xl">
    <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Create a website</h2>
    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">It only takes a minute — no credit card required.</p>

    @if ($step < 5)
        <div class="mt-6 flex items-center gap-2">
            @for ($i = 1; $i <= 4; $i++)
                <div class="h-1.5 flex-1 rounded-full {{ $i <= $step ? 'bg-harbor-500' : 'bg-surface-200 dark:bg-white/10' }}"></div>
            @endfor
        </div>
    @endif

    <div class="card mt-6">
        {{-- Step 1: project type --}}
        @if ($step === 1)
            <h3 class="mb-4 font-semibold text-slate-800 dark:text-slate-100">1. Choose a project type</h3>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                @foreach ($projectTypes as $key => $type)
                    <button wire:click="selectProjectType('{{ $key }}')" type="button" class="card-flat flex flex-col items-center gap-2 p-4 text-center transition hover:border-harbor-400 hover:shadow-soft">
                        <x-dynamic-component :component="'heroicon-o-'.$type['icon']" class="h-6 w-6 text-harbor-600 dark:text-harbor-400" />
                        <span class="text-xs font-medium text-slate-700 dark:text-slate-200">{{ $type['label'] }}</span>
                    </button>
                @endforeach
            </div>
        @endif

        {{-- Step 2: name --}}
        @if ($step === 2)
            <h3 class="mb-4 font-semibold text-slate-800 dark:text-slate-100">2. Name your website</h3>
            <label class="label">Website name</label>
            <input type="text" wire:model="name" wire:keydown.enter="nextStep" autofocus class="input" placeholder="My awesome project">
            @error('name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror

            @if ($projectType === 'git')
                <div class="mt-4">
                    <label class="label">Git repository URL</label>
                    <input type="text" wire:model="gitUrl" class="input" placeholder="https://github.com/you/repo.git">
                </div>
                <div class="mt-4">
                    <label class="label">Branch</label>
                    <input type="text" wire:model="gitBranch" class="input" placeholder="main">
                </div>
            @endif

            <div class="mt-6 flex justify-between">
                <button wire:click="previousStep" class="btn-secondary">Back</button>
                <button wire:click="nextStep" class="btn-primary">Continue</button>
            </div>
        @endif

        {{-- Step 3: domain --}}
        @if ($step === 3)
            <h3 class="mb-4 font-semibold text-slate-800 dark:text-slate-100">3. Choose a domain</h3>
            <div class="space-y-3">
                <label class="flex items-start gap-3 rounded-lg border border-surface-200 p-3 dark:border-white/10">
                    <input type="radio" wire:model="domainChoice" value="temporary" class="mt-1">
                    <span>
                        <span class="block text-sm font-medium text-slate-800 dark:text-slate-100">Use a free Portway subdomain</span>
                        <span class="block text-xs text-slate-500 dark:text-slate-400">yoursite.{{ config('portway.temporary_domain_suffix') }} — ready instantly</span>
                    </span>
                </label>
                <label class="flex items-start gap-3 rounded-lg border border-surface-200 p-3 dark:border-white/10">
                    <input type="radio" wire:model="domainChoice" value="custom" class="mt-1">
                    <span class="w-full">
                        <span class="block text-sm font-medium text-slate-800 dark:text-slate-100">Connect a domain I already own</span>
                        <span class="mt-2 block text-xs text-slate-500 dark:text-slate-400">You can also do this later from the Domains page.</span>
                    </span>
                </label>
            </div>

            <div class="mt-6 flex justify-between">
                <button wire:click="previousStep" class="btn-secondary">Back</button>
                <button wire:click="nextStep" class="btn-primary">Continue</button>
            </div>
        @endif

        {{-- Step 4: runtime + create --}}
        @if ($step === 4)
            <h3 class="mb-4 font-semibold text-slate-800 dark:text-slate-100">4. Choose your runtime</h3>

            @if ($projectTypes[$projectType]['runtime'] === 'php')
                <label class="label">PHP version</label>
                <select wire:model="phpVersion" class="input">
                    @foreach ($phpVersions as $version)
                        <option value="{{ $version }}">PHP {{ $version }}</option>
                    @endforeach
                </select>
            @elseif ($projectTypes[$projectType]['runtime'] === 'node')
                <label class="label">Node.js version</label>
                <select wire:model="nodeVersion" class="input">
                    @foreach ($nodeVersions as $version)
                        <option value="{{ $version }}">Node {{ $version }}</option>
                    @endforeach
                </select>
            @else
                <p class="text-sm text-slate-500 dark:text-slate-400">This project type doesn't need a runtime version — it's served as static files.</p>
            @endif

            <div class="mt-6 rounded-lg bg-surface-50 p-3 text-sm text-slate-600 dark:bg-white/5 dark:text-slate-300">
                <p><span class="font-medium">{{ $name }}</span> · {{ $projectTypes[$projectType]['label'] }}</p>
            </div>

            <div class="mt-6 flex justify-between">
                <button wire:click="previousStep" class="btn-secondary">Back</button>
                <button wire:click="create" wire:loading.attr="disabled" class="btn-primary">
                    <span wire:loading.remove wire:target="create">Create Website</span>
                    <span wire:loading wire:target="create">Creating…</span>
                </button>
            </div>
        @endif

        {{-- Step 5: progress --}}
        @if ($step === 5 && $site)
            <div wire:poll.2s>
                <h3 class="mb-4 font-semibold text-slate-800 dark:text-slate-100">Setting up {{ $site->name }}</h3>

                <x-progress-bar :percent="$site->provisioning_progress" class="mb-3" />

                <p class="text-sm text-slate-600 dark:text-slate-300">{{ $site->status_message ?? 'Starting…' }}</p>

                @if ($site->status->value === 'active')
                    <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                        <p class="font-medium">Website ready.</p>
                        <p class="text-sm">Your site is live at {{ $site->temporaryHostname() }}.</p>
                    </div>
                    <a href="{{ route('sites.show', $site) }}" wire:navigate class="btn-primary mt-4 inline-flex">Go to website</a>
                @elseif ($site->status->value === 'failed')
                    <div class="mt-6 rounded-lg border border-rose-200 bg-rose-50 p-4 text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300">
                        <p class="font-medium">Something went wrong.</p>
                        <p class="text-sm">{{ $site->status_message }}</p>
                    </div>
                    <a href="{{ route('sites.index') }}" wire:navigate class="btn-secondary mt-4 inline-flex">Back to websites</a>
                @endif
            </div>
        @endif
    </div>
</div>
