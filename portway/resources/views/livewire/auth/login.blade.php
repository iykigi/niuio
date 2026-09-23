<div class="mx-auto flex min-h-[calc(100vh-8rem)] max-w-sm flex-col justify-center px-4 py-12">
    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Log in to {{ config('app.brand.name') }}</h1>
    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Manage your free websites, domains and databases.</p>

    <form wire:submit="login" class="mt-6 space-y-4">
        <div>
            <label class="label">Email address</label>
            <input type="email" wire:model="email" required autofocus class="input" placeholder="you@example.com">
            @error('email') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <div class="flex items-center justify-between">
                <label class="label">Password</label>
                <a href="{{ route('password.request') }}" wire:navigate class="text-sm text-harbor-600 dark:text-harbor-400">Forgot password?</a>
            </div>
            <input type="password" wire:model="password" required class="input" placeholder="••••••••">
            @error('password') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
            <input type="checkbox" wire:model="remember" class="rounded border-surface-300 text-harbor-600 focus:ring-harbor-500">
            Remember me
        </label>

        <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled">
            <span wire:loading.remove>Log in</span>
            <span wire:loading>Logging in…</span>
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
        Don't have an account?
        <a href="{{ route('register') }}" wire:navigate class="font-medium text-harbor-600 dark:text-harbor-400">Start hosting free</a>
    </p>
</div>
