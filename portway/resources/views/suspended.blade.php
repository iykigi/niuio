@component('layouts.guest')
    <div class="mx-auto flex min-h-[calc(100vh-8rem)] max-w-md flex-col items-center justify-center px-4 py-12 text-center">
        <span class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-rose-100 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300">
            <x-heroicon-o-exclamation-triangle class="h-6 w-6" />
        </span>
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Your account is suspended</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            @if ($user->suspension_reason)
                Reason: {{ $user->suspension_reason }}
            @else
                Please contact support for details. Your websites and data have not been deleted.
            @endif
        </p>

        <a href="{{ route('support.index') }}" class="btn-primary mt-6">Contact support</a>

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="text-sm text-slate-500 dark:text-slate-400">Log out</button>
        </form>
    </div>
@endcomponent
