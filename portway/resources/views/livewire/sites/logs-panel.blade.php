<div class="card">
    <div class="mb-4 flex items-center justify-between">
        <h3 class="font-semibold text-slate-800 dark:text-slate-100">Logs</h3>
        <button wire:click="refresh" wire:loading.attr="disabled" class="btn-secondary !py-1.5 text-sm">Refresh</button>
    </div>

    @if (empty($files))
        <x-empty-state icon="document-text" title="No log files yet" description="Logs appear here once the website has served a request, run a deployment, or executed a scheduled process." />
    @else
        <div class="flex flex-col gap-4 md:flex-row">
            <ul class="flex shrink-0 flex-row gap-1 overflow-x-auto md:w-56 md:flex-col md:overflow-visible">
                @foreach ($files as $file)
                    <li>
                        <button
                            wire:click="open(@js($file['path']))"
                            class="w-full rounded-lg px-3 py-2 text-left text-sm {{ $activeFile === $file['path'] ? 'bg-harbor-50 font-medium text-harbor-700 dark:bg-harbor-500/10 dark:text-harbor-300' : 'text-slate-600 hover:bg-surface-50 dark:text-slate-300 dark:hover:bg-white/5' }}"
                        >
                            <span class="block truncate font-mono">{{ $file['name'] }}</span>
                            <span class="block text-xs text-slate-400">{{ number_format(($file['size'] ?? 0) / 1024, 1) }} KB</span>
                        </button>
                    </li>
                @endforeach
            </ul>

            <div class="min-w-0 flex-1">
                @if ($tail !== null)
                    <pre class="h-96 overflow-auto rounded-lg bg-slate-900 p-4 text-xs text-slate-100">{{ $tail !== '' ? $tail : 'This log file is empty.' }}</pre>
                    <p class="mt-2 text-xs text-slate-400">Showing the last {{ $lines }} lines.</p>
                @else
                    <p class="text-sm text-slate-500 dark:text-slate-400">Select a log file to view its contents.</p>
                @endif
            </div>
        </div>
    @endif
</div>
