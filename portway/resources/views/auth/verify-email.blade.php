@component('layouts.guest')
    <div class="mx-auto flex min-h-[calc(100vh-8rem)] max-w-md flex-col items-center justify-center px-4 py-12 text-center">
        <span class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-harbor-100 text-harbor-600 dark:bg-harbor-500/10 dark:text-harbor-300">
            <x-heroicon-o-envelope class="h-6 w-6" />
        </span>
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Verify your email address</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            We sent a verification link to {{ auth()->user()->email }}. Click it to activate your account.
        </p>

        @if (session('status') === 'verification-link-sent')
            <p class="mt-4 text-sm text-emerald-600 dark:text-emerald-400">A new verification link has been sent.</p>
        @endif

        <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
            @csrf
            <button type="submit" class="btn-secondary">Resend verification email</button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="text-sm text-slate-500 dark:text-slate-400">Log out</button>
        </form>
    </div>
@endcomponent
