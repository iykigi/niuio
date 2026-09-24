<?php

namespace App\Livewire\Databases;

use App\Models\Database;
use App\Services\Databases\DatabaseManager;
use App\Services\Databases\DatabaseProvisioningService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class DatabaseDetail extends Component
{
    use WithFileUploads;

    public Database $database;

    public string $tab = 'structure';

    public ?string $selectedTable = null;

    public string $rowSearch = '';

    public int $page = 1;

    public string $sql = '';

    public ?array $sqlResult = null;

    public ?string $sqlError = null;

    public array $editingRow = [];

    public ?array $editingPrimaryKey = null;

    public bool $showRowModal = false;

    public $importFile = null;

    public function mount(Database $database, ?string $tab = null): void
    {
        $this->authorize('view', $database);
        $this->database = $database;
        $this->tab = in_array($tab, ['structure', 'browse', 'query', 'import-export', 'credentials'], true) ? $tab : 'structure';
    }

    private function manager(): DatabaseManager
    {
        return new DatabaseManager($this->database);
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['structure', 'browse', 'query', 'import-export', 'credentials'], true) ? $tab : 'structure';
    }

    public function selectTable(string $table): void
    {
        $this->selectedTable = $table;
        $this->page = 1;
        $this->tab = 'browse';
    }

    public function openNewRow(): void
    {
        $this->editingRow = array_fill_keys(array_column($this->manager()->tableColumns($this->selectedTable), 'name'), '');
        $this->editingPrimaryKey = null;
        $this->showRowModal = true;
    }

    public function openEditRow(array $row): void
    {
        $columns = $this->manager()->tableColumns($this->selectedTable);
        $primary = array_column(array_filter($columns, fn ($c) => $c['primary_key']), 'name');

        $this->editingRow = $row;
        $this->editingPrimaryKey = array_intersect_key($row, array_flip($primary));
        $this->showRowModal = true;
    }

    public function saveRow(): void
    {
        $this->authorize('runQuery', $this->database);

        // Empty inputs mean "use the column default" (e.g. an auto-increment
        // id) when inserting, rather than forcing an empty string into it.
        $data = $this->editingPrimaryKey
            ? $this->editingRow
            : array_filter($this->editingRow, fn ($value) => $value !== '' && $value !== null);

        try {
            if ($this->editingPrimaryKey) {
                $this->manager()->updateRow($this->selectedTable, $this->editingPrimaryKey, $data);
            } else {
                $this->manager()->insertRow($this->selectedTable, $data);
            }
        } catch (\Throwable $e) {
            $this->dispatch('toast', message: $e->getMessage(), level: 'danger');

            return;
        }

        $this->showRowModal = false;
        $this->dispatch('toast', message: 'Row saved.', level: 'success');
    }

    public function deleteRow(array $primaryKey): void
    {
        $this->authorize('runQuery', $this->database);

        try {
            $this->manager()->deleteRow($this->selectedTable, $primaryKey);
        } catch (\Throwable $e) {
            $this->dispatch('toast', message: $e->getMessage(), level: 'danger');

            return;
        }

        $this->dispatch('toast', message: 'Row deleted.', level: 'success');
    }

    public function runQuery(): void
    {
        $this->authorize('runQuery', $this->database);
        $this->sqlError = null;

        try {
            $this->sqlResult = $this->manager()->runQuery($this->sql);
        } catch (\Throwable $e) {
            $this->sqlResult = null;
            $this->sqlError = $e->getMessage();
        }
    }

    public function exportSql()
    {
        $this->authorize('view', $this->database);
        $content = $this->manager()->exportSql();
        $filename = "{$this->database->name}-".now()->format('Ymd-His').'.sql';

        return response()->streamDownload(fn () => print ($content), $filename);
    }

    public function importSql(): void
    {
        $this->authorize('runQuery', $this->database);
        $this->validate(['importFile' => ['required', 'file', 'max:20480']]);

        try {
            $this->manager()->importSql(file_get_contents($this->importFile->getRealPath()));
        } catch (\Throwable $e) {
            $this->addError('importFile', $e->getMessage());

            return;
        }

        $this->importFile = null;
        $this->dispatch('toast', message: 'Import complete.', level: 'success');
    }

    public function regeneratePassword(DatabaseProvisioningService $service): void
    {
        $this->authorize('update', $this->database);
        $user = $this->database->databaseUsers()->first();
        $plain = $service->regeneratePassword($user);
        $this->dispatch('toast', message: "Password changed: {$plain} (copy it now, it won't be shown again).", level: 'success');
    }

    public function render()
    {
        $tables = $columns = $rows = [];
        $rowCount = 0;
        $browseError = null;

        // A table whose name the manager refuses (spaces, dashes, ...) or
        // an unreachable server must not take the whole page down with it.
        try {
            $tables = $this->manager()->listTables();

            if (! in_array($this->selectedTable, $tables, true)) {
                $this->selectedTable = $tables[0] ?? null;
            }

            if ($this->selectedTable) {
                $columns = $this->manager()->tableColumns($this->selectedTable);
                $rows = $this->manager()->browseRows($this->selectedTable, $this->page, 25, $this->rowSearch);
                $rowCount = $this->manager()->rowCount($this->selectedTable);
            }
        } catch (\Throwable $e) {
            report($e);
            $browseError = $e->getMessage();
        }

        return view('livewire.databases.database-detail', [
            'tables' => $tables,
            'columns' => $columns,
            'rows' => $rows,
            'rowCount' => $rowCount,
            'browseError' => $browseError,
            'databaseUser' => $this->database->databaseUsers()->first(),
        ]);
    }
}
