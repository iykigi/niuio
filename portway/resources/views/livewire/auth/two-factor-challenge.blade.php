<div class="mx-auto flex min-h-[calc(100vh-8rem)] max-w-sm flex-col justify-center px-4 py-12">
    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Two-factor verification</h1>
    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
        @if ($useRecoveryCode)
            Enter one of your unused recovery codes.
        @else
            Enter the 6-digit code from your authenticator app.
        @endif
    </p>

    <form wire:submit="verify" class="mt-6 space-y-4">
        @if ($useRecoveryCode)
            <div>
                <label class="label">Recovery code</label>
                <input type="text" wire:model="recoveryCode" required autofocus class="input font-mono">
                @error('recoveryCode') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
        @else
            <div>
                <label class="label">Authentication code</label>
                <input type="text" inputmode="numeric" maxlength="6" wire:model="code" required autofocus class="input text-center text-lg tracking-widest">
                @error('code') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
        @endif

        <button type="submit" class="btn-primary w-full">Verify</button>
    </form>

    <button type="button" wire:click="$toggle('useRecoveryCode')" class="mt-4 text-center text-sm text-harbor-600 dark:text-harbor-400">
        {{ $useRecoveryCode ? 'Use an authenticator code instead' : 'Use a recovery code instead' }}
    </button>
</div>
