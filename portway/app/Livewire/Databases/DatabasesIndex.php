<?php

namespace App\Livewire\Databases;

use App\Models\Database;
use App\Services\Databases\DatabaseProvisioningService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Databases')]
class DatabasesIndex extends Component
{
    public bool $showCreateModal = false;

    public string $label = '';

    public ?int $siteId = null;

    public ?array $justCreatedCredentials = null;

    public function create(DatabaseProvisioningService $service): void
    {
        $this->validate(['label' => ['required', 'string', 'min:2', 'max:40']]);

        try {
            $site = $this->siteId ? \App\Models\Site::where('user_id', auth()->id())->find($this->siteId) : null;
            $result = $service->create(auth()->user(), $this->label, $site);

            $this->justCreatedCredentials = [
                'database' => $result['database']->name,
                'username' => $result['user']->username,
                'password' => $result['plain_password'],
                'host' => $result['database']->host,
            ];

            $this->showCreateModal = false;
            $this->label = '';
        } catch (\Throwable $e) {
            $this->addError('label', $e->getMessage());
        }
    }

    public function delete(int $databaseId, DatabaseProvisioningService $service): void
    {
        $database = Database::where('user_id', auth()->id())->findOrFail($databaseId);
        $this->authorize('delete', $database);

        $service->delete($database);
        $this->dispatch('toast', message: 'Database deleted.', level: 'success');
    }

    public function render()
    {
        return view('livewire.databases.databases-index', [
            'databases' => Database::where('user_id', auth()->id())->with('site')->latest()->get(),
            'sites' => auth()->user()->sites()->get(),
        ]);
    }
}
