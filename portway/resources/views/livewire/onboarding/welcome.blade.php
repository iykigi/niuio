<div class="mx-auto max-w-2xl py-8">
    <div class="mb-8 flex items-center justify-center gap-2">
        @for ($i = 1; $i <= 3; $i++)
            <span class="h-1.5 w-16 rounded-full {{ $i <= $step ? 'bg-harbor-600' : 'bg-surface-200 dark:bg-white/10' }}"></span>
        @endfor
    </div>

    <div class="card text-center">
        @if ($step === 1)
            <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-harbor-50 text-harbor-600 dark:bg-harbor-500/10 dark:text-harbor-300">
                <x-heroicon-o-sparkles class="h-7 w-7" />
            </span>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Welcome to Portway, {{ auth()->user()->name }}</h1>
            <p class="mx-auto mt-2 max-w-md text-sm text-slate-500 dark:text-slate-400">
                Free hosting for your websites — no payment details, no subscriptions, ever. Let's get you set up in under a minute.
            </p>
        @elseif ($step === 2)
            <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-harbor-50 text-harbor-600 dark:bg-harbor-500/10 dark:text-harbor-300">
                <x-heroicon-o-globe-alt class="h-7 w-7" />
            </span>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Everything you need, in one dashboard</h1>
            <div class="mx-auto mt-4 grid max-w-md grid-cols-2 gap-3 text-left text-sm">
                <div class="rounded-lg bg-surface-50 p-3 dark:bg-white/5"><x-heroicon-o-folder class="mb-1 h-5 w-5 text-harbor-600 dark:text-harbor-300" /> File manager &amp; Git deploys</div>
                <div class="rounded-lg bg-surface-50 p-3 dark:bg-white/5"><x-heroicon-o-circle-stack class="mb-1 h-5 w-5 text-harbor-600 dark:text-harbor-300" /> Databases &amp; backups</div>
                <div class="rounded-lg bg-surface-50 p-3 dark:bg-white/5"><x-heroicon-o-shield-check class="mb-1 h-5 w-5 text-harbor-600 dark:text-harbor-300" /> Free SSL &amp; security</div>
                <div class="rounded-lg bg-surface-50 p-3 dark:bg-white/5"><x-heroicon-o-command-line class="mb-1 h-5 w-5 text-harbor-600 dark:text-harbor-300" /> Terminal &amp; cron jobs</div>
            </div>
        @else
            <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-harbor-50 text-harbor-600 dark:bg-harbor-500/10 dark:text-harbor-300">
                <x-heroicon-o-rocket-launch class="h-7 w-7" />
            </span>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Ready to launch your first website?</h1>
            <p class="mx-auto mt-2 max-w-md text-sm text-slate-500 dark:text-slate-400">
                Start from a blank PHP or Node.js app, import a Git repository, or upload your existing files — you'll have a temporary Portway address instantly and can connect your own domain any time.
            </p>
        @endif

        <div class="mt-6 flex items-center justify-center gap-2">
            @if ($step > 1)
                <button wire:click="back" class="btn-secondary">Back</button>
            @endif

            @if ($step < 3)
                <button wire:click="next" class="btn-primary">Continue</button>
            @else
                <button wire:click="finish" class="btn-primary">Create my first website</button>
            @endif

            @if ($step === 1 || $step === 3)
                <button wire:click="skip" class="btn-ghost">{{ $step === 3 ? 'Go to dashboard instead' : 'Skip' }}</button>
            @endif
        </div>
    </div>
</div>
