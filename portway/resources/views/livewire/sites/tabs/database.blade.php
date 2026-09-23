<div class="card">
    <div class="mb-3 flex items-center justify-between">
        <h3 class="font-semibold text-slate-800 dark:text-slate-100">Databases for this website</h3>
        <a href="{{ route('databases.index') }}" wire:navigate class="btn-secondary !py-1.5 text-sm">Manage all databases</a>
    </div>
    <ul class="divide-y divide-surface-100 dark:divide-white/5">
        @forelse ($site->databases as $database)
            <li class="flex items-center justify-between py-3 text-sm">
                <span class="font-mono text-slate-700 dark:text-slate-200">{{ $database->name }}</span>
                <div class="flex items-center gap-2">
                    <span class="text-slate-400">{{ $database->sizeHuman() }}</span>
                    <a href="{{ route('databases.show', $database) }}" wire:navigate class="btn-ghost !p-1.5"><x-heroicon-o-arrow-right class="h-4 w-4" /></a>
                </div>
            </li>
        @empty
            <li class="py-6 text-center text-slate-400">No databases attached to this website yet.</li>
        @endforelse
    </ul>
</div>
