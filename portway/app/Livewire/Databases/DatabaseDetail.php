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
        $this->tab = $tab;
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

        if ($this->editingPrimaryKey) {
            $this->manager()->updateRow($this->selectedTable, $this->editingPrimaryKey, $this->editingRow);
        } else {
            $this->manager()->insertRow($this->selectedTable, $this->editingRow);
        }

        $this->showRowModal = false;
        $this->dispatch('toast', message: 'Row saved.', level: 'success');
    }

    public function deleteRow(array $primaryKey): void
    {
        $this->authorize('runQuery', $this->database);
        $this->manager()->deleteRow($this->selectedTable, $primaryKey);
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

        $this->manager()->importSql(file_get_contents($this->importFile->getRealPath()));
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
        $tables = $this->manager()->listTables();
        $this->selectedTable ??= $tables[0] ?? null;

        $columns = $this->selectedTable ? $this->manager()->tableColumns($this->selectedTable) : [];
        $rows = $this->selectedTable ? $this->manager()->browseRows($this->selectedTable, $this->page, 25, $this->rowSearch) : [];
        $rowCount = $this->selectedTable ? $this->manager()->rowCount($this->selectedTable) : 0;

        return view('livewire.databases.database-detail', [
            'tables' => $tables,
            'columns' => $columns,
            'rows' => $rows,
            'rowCount' => $rowCount,
            'databaseUser' => $this->database->databaseUsers()->first(),
        ]);
    }
}
