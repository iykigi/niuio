<div>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white font-mono">{{ $database->name }}</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $database->sizeHuman() }} · {{ $database->host }}:{{ $database->port }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
        <div class="card !p-0 lg:col-span-1">
            <p class="border-b border-surface-200 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-white/10 dark:text-slate-400">Tables</p>
            <ul class="max-h-96 overflow-y-auto">
                @forelse ($tables as $table)
                    <li>
                        <button wire:click="selectTable(@js($table))" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm {{ $selectedTable === $table ? 'bg-harbor-50 text-harbor-700 dark:bg-harbor-500/10 dark:text-harbor-300' : 'text-slate-600 dark:text-slate-300' }}">
                            <x-heroicon-o-table-cells class="h-4 w-4" /> {{ $table }}
                        </button>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center text-sm text-slate-400">No tables yet.</li>
                @endforelse
            </ul>
        </div>

        <div class="lg:col-span-3">
            <div class="mb-4 flex gap-1 border-b border-surface-200 dark:border-white/10">
                @foreach (['structure' => 'Structure', 'browse' => 'Browse', 'query' => 'SQL', 'import-export' => 'Import / Export', 'credentials' => 'Credentials'] as $key => $label)
                    <button wire:click="setTab(@js($key))" class="border-b-2 px-3 py-2.5 text-sm font-medium {{ $tab === $key ? 'border-harbor-600 text-harbor-700 dark:border-harbor-400 dark:text-harbor-300' : 'border-transparent text-slate-500 dark:text-slate-400' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            @if ($browseError)
                <div class="mb-4 rounded-lg bg-rose-50 p-3 text-sm text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">{{ $browseError }}</div>
            @endif

            @if ($tab === 'structure' && $selectedTable)
                <div class="card overflow-hidden !p-0">
                    <table class="w-full text-sm">
                        <thead class="border-b border-surface-200 bg-surface-50 text-left text-xs uppercase text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-400">
                            <tr><th class="px-4 py-2">Column</th><th class="px-4 py-2">Type</th><th class="px-4 py-2">Nullable</th><th class="px-4 py-2">Key</th><th class="px-4 py-2">Default</th></tr>
                        </thead>
                        <tbody class="divide-y divide-surface-100 dark:divide-white/5">
                            @foreach ($columns as $column)
                                <tr>
                                    <td class="px-4 py-2 font-mono">{{ $column['name'] }}</td>
                                    <td class="px-4 py-2 text-slate-500 dark:text-slate-400">{{ $column['type'] }}</td>
                                    <td class="px-4 py-2">{{ $column['nullable'] ? 'Yes' : 'No' }}</td>
                                    <td class="px-4 py-2">{{ $column['primary_key'] ? 'PRI' : '' }}</td>
                                    <td class="px-4 py-2 text-slate-400">{{ $column['default'] ?? 'NULL' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($tab === 'browse' && $selectedTable)
                @php
                    $primaryColumns = array_column(array_filter($columns, fn ($c) => $c['primary_key']), 'name');
                @endphp
                <div class="mb-3 flex items-center justify-between">
                    <input type="text" wire:model.live.debounce.300ms="rowSearch" placeholder="Search rows…" class="input !w-56">
                    <button wire:click="openNewRow" class="btn-primary !py-1.5 text-sm"><x-heroicon-o-plus class="h-4 w-4" /> Insert row</button>
                </div>
                <div class="card overflow-x-auto !p-0">
                    <table class="w-full text-sm">
                        <thead class="border-b border-surface-200 bg-surface-50 text-left text-xs uppercase text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-400">
                            <tr>
                                @foreach ($columns as $column)
                                    <th class="whitespace-nowrap px-3 py-2">{{ $column['name'] }}</th>
                                @endforeach
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-100 dark:divide-white/5">
                            @forelse ($rows as $row)
                                <tr>
                                    @foreach ($columns as $column)
                                        <td class="max-w-[200px] truncate whitespace-nowrap px-3 py-2 font-mono text-xs">{{ $row[$column['name']] ?? 'NULL' }}</td>
                                    @endforeach
                                    <td class="whitespace-nowrap px-3 py-2 text-right">
                                        {{-- Rows can only be edited/deleted by primary key; without one there is no safe way to target a single row. --}}
                                        @if ($primaryColumns)
                                            <button wire:click="openEditRow(@js($row))" class="btn-ghost !p-1.5" title="Edit row"><x-heroicon-o-pencil class="h-4 w-4" /></button>
                                            <button wire:click="deleteRow(@js(array_intersect_key($row, array_flip($primaryColumns))))" wire:confirm="Delete this row?" class="btn-ghost !p-1.5 text-rose-600" title="Delete row"><x-heroicon-o-trash class="h-4 w-4" /></button>
                                        @else
                                            <span class="text-xs text-slate-400" title="Add a primary key to this table to edit rows here">read-only</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ count($columns) + 1 }}" class="px-3 py-8 text-center text-slate-400">No rows.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 flex items-center justify-between text-sm text-slate-500 dark:text-slate-400">
                    <span>{{ $rowCount }} row(s)</span>
                    <div class="flex gap-2">
                        <button wire:click="$set('page', {{ max(1, $page - 1) }})" class="btn-secondary !py-1 !px-3 text-xs">Prev</button>
                        <button wire:click="$set('page', {{ $page + 1 }})" class="btn-secondary !py-1 !px-3 text-xs">Next</button>
                    </div>
                </div>
            @endif

            @if ($tab === 'query')
                <div class="card">
                    <textarea wire:model="sql" rows="6" class="input font-mono text-sm" placeholder="SELECT * FROM table_name LIMIT 25;"></textarea>
                    <button wire:click="runQuery" class="btn-primary mt-3">Run query</button>

                    @if ($sqlError)
                        <p class="mt-3 text-sm text-rose-600">{{ $sqlError }}</p>
                    @endif

                    @if ($sqlResult && ! empty($sqlResult['rows']))
                        <div class="mt-4 overflow-x-auto rounded-lg border border-surface-200 dark:border-white/10">
                            <table class="w-full text-sm">
                                <thead class="bg-surface-50 text-left text-xs uppercase text-slate-500 dark:bg-white/5 dark:text-slate-400">
                                    <tr>@foreach (array_keys($sqlResult['rows'][0]) as $col)<th class="px-3 py-2">{{ $col }}</th>@endforeach</tr>
                                </thead>
                                <tbody class="divide-y divide-surface-100 dark:divide-white/5">
                                    @foreach ($sqlResult['rows'] as $row)
                                        <tr>@foreach ($row as $value)<td class="px-3 py-2 font-mono text-xs">{{ $value }}</td>@endforeach</tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @elseif ($sqlResult)
                        <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">{{ $sqlResult['affected'] }} row(s) affected.</p>
                    @endif
                </div>
            @endif

            @if ($tab === 'import-export')
                <div class="card space-y-6">
                    <div>
                        <h3 class="mb-2 font-semibold text-slate-800 dark:text-slate-100">Export</h3>
                        <button wire:click="exportSql" class="btn-secondary"><x-heroicon-o-arrow-down-tray class="h-4 w-4" /> Download SQL dump</button>
                    </div>
                    <div>
                        <h3 class="mb-2 font-semibold text-slate-800 dark:text-slate-100">Import</h3>
                        <input type="file" wire:model="importFile" accept=".sql" class="input">
                        @error('importFile') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                        <button wire:click="importSql" wire:loading.attr="disabled" class="btn-primary mt-3">Import</button>
                    </div>
                </div>
            @endif

            @if ($tab === 'credentials')
                <div class="card">
                    <dl class="grid grid-cols-2 gap-3 font-mono text-sm">
                        <dt class="text-slate-400">Host</dt><dd>{{ $database->host }}</dd>
                        <dt class="text-slate-400">Port</dt><dd>{{ $database->port }}</dd>
                        <dt class="text-slate-400">Database</dt><dd>{{ $database->name }}</dd>
                        <dt class="text-slate-400">Username</dt><dd>{{ $databaseUser?->username }}</dd>
                    </dl>
                    <button wire:click="regeneratePassword" wire:confirm="This invalidates the old password. Continue?" class="btn-secondary mt-4">Change password</button>
                </div>
            @endif
        </div>
    </div>

    <x-modal :show="$showRowModal" wire:click.outside="$set('showRowModal', false)" max-width="lg">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $editingPrimaryKey ? 'Edit row' : 'Insert row' }}</h3>
        <div class="mt-4 max-h-96 space-y-3 overflow-y-auto">
            @foreach ($columns as $column)
                <div>
                    <label class="label">{{ $column['name'] }}</label>
                    <input type="text" wire:model="editingRow.{{ $column['name'] }}" class="input font-mono text-sm">
                </div>
            @endforeach
        </div>
        <div class="mt-5 flex justify-end gap-2">
            <button wire:click="$set('showRowModal', false)" class="btn-secondary">Cancel</button>
            <button wire:click="saveRow" class="btn-primary">Save</button>
        </div>
    </x-modal>
</div>
