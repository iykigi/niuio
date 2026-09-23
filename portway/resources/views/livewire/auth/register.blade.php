<div class="mx-auto flex min-h-[calc(100vh-8rem)] max-w-sm flex-col justify-center px-4 py-12">
    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Start hosting for free</h1>
    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">10 GB storage, free SSL, no credit card required.</p>

    <form wire:submit="register" class="mt-6 space-y-4">
        <div>
            <label class="label">Full name</label>
            <input type="text" wire:model="name" required autofocus class="input" placeholder="Jane Doe">
            @error('name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label">Email address</label>
            <input type="email" wire:model="email" required class="input" placeholder="you@example.com">
            @error('email') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label">Password</label>
            <input type="password" wire:model="password" required class="input" placeholder="At least 10 characters">
            @error('password') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label">Confirm password</label>
            <input type="password" wire:model="password_confirmation" required class="input">
        </div>

        <label class="flex items-start gap-2 text-sm text-slate-600 dark:text-slate-300">
            <input type="checkbox" wire:model="terms" class="mt-0.5 rounded border-surface-300 text-harbor-600 focus:ring-harbor-500">
            <span>I agree to the Terms of Service and Acceptable Use Policy.</span>
        </label>
        @error('terms') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror

        <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled">
            <span wire:loading.remove>Create free account</span>
            <span wire:loading>Creating account…</span>
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
        Already have an account?
        <a href="{{ route('login') }}" wire:navigate class="font-medium text-harbor-600 dark:text-harbor-400">Log in</a>
    </p>
</div>
