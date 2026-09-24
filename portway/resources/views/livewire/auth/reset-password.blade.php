<div class="mx-auto flex min-h-[calc(100vh-8rem)] max-w-sm flex-col justify-center px-4 py-12">
    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Choose a new password</h1>

    <form wire:submit="resetPassword" class="mt-6 space-y-4">
        <div>
            <label class="label">Email address</label>
            <input type="email" wire:model="email" required class="input">
            @error('email') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label">New password</label>
            <input type="password" wire:model="password" required autofocus class="input">
            @error('password') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label">Confirm new password</label>
            <input type="password" wire:model="password_confirmation" required class="input">
        </div>

        <button type="submit" class="btn-primary w-full">Reset password</button>
    </form>
</div>
