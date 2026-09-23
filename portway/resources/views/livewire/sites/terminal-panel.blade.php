<div class="card" wire:key="terminal-panel">
    <div class="mb-3 flex items-center justify-between">
        <div>
            <h3 class="font-semibold text-slate-800 dark:text-slate-100">Web terminal</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">Runs inside this website's document root. Only an allowlisted set of programs can be executed — see the security notice below.</p>
        </div>
        <button wire:click="clear" class="btn-ghost !py-1.5 text-sm">Clear</button>
    </div>

    <div
        x-data="{ scrollToBottom() { this.$el.scrollTop = this.$el.scrollHeight } }"
        x-init="scrollToBottom()"
        x-effect="$wire.history.length && scrollToBottom()"
        class="h-96 overflow-y-auto rounded-lg bg-slate-900 p-4 font-mono text-sm text-slate-100"
    >
        @forelse ($history as $entry)
            <div class="mb-3">
                <div class="flex items-center gap-2 text-harbor-300">
                    <span>$</span>
                    <span>{{ $entry['command'] }}</span>
                </div>
                @if ($entry['output'] !== '')
                    <pre class="mt-1 whitespace-pre-wrap text-slate-200">{{ $entry['output'] }}</pre>
                @endif
                @if ($entry['exit_code'] !== 0)
                    <p class="mt-1 text-xs text-rose-400">exit code {{ $entry['exit_code'] }}</p>
                @endif
            </div>
        @empty
            <p class="text-slate-500">Type a command below and press Enter to run it.</p>
        @endforelse
    </div>

    <form wire:submit="run" class="mt-3 flex items-center gap-2">
        <span class="font-mono text-slate-400">$</span>
        <input
            type="text"
            wire:model="command"
            autocomplete="off"
            autofocus
            placeholder="php artisan migrate --force"
            class="input flex-1 font-mono text-sm"
        >
        <button type="submit" class="btn-primary">Run</button>
    </form>

    <p class="mt-3 text-xs text-slate-400">
        Allowed programs include php, composer, artisan, node, npm, git, wp and common file utilities. Commands that
        touch system files, escalate privileges, or attempt to escape this website's directory are blocked.
    </p>
</div>
