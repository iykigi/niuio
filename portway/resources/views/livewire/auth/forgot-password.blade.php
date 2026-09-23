<div class="mx-auto flex min-h-[calc(100vh-8rem)] max-w-sm flex-col justify-center px-4 py-12">
    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Reset your password</h1>
    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">We'll email you a secure link.</p>

    @if ($status)
        <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
            {{ $status }}
        </div>
    @else
        <form wire:submit="sendResetLink" class="mt-6 space-y-4">
            <div>
                <label class="label">Email address</label>
                <input type="email" wire:model="email" required autofocus class="input" placeholder="you@example.com">
                @error('email') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="btn-primary w-full">Send reset link</button>
        </form>
    @endif

    <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
        <a href="{{ route('login') }}" wire:navigate class="font-medium text-harbor-600 dark:text-harbor-400">Back to log in</a>
    </p>
</div>
