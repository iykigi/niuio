<?php

namespace App\Livewire\Admin;

use App\Models\Server;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ServersManager extends Component
{
    public bool $showCreate = false;

    public string $name = '';

    public string $hostname = '';

    public string $ipAddress = '';

    public string $region = '';

    public string $role = 'combined';

    public string $webServer = 'nginx';

    public int $maxSites = 500;

    public string $sshUser = 'portway';

    public int $sshPort = 22;

    public string $sshPrivateKey = '';

    public ?int $deleteTargetId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Server::class);
    }

    public function openCreate(): void
    {
        $this->reset([
            'name', 'hostname', 'ipAddress', 'region', 'role', 'webServer',
            'maxSites', 'sshUser', 'sshPort', 'sshPrivateKey',
        ]);
        $this->role = 'combined';
        $this->webServer = 'nginx';
        $this->maxSites = 500;
        $this->sshUser = 'portway';
        $this->sshPort = 22;
        $this->showCreate = true;
    }

    public function create(): void
    {
        $this->authorize('create', Server::class);

        $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'hostname' => ['required', 'string', 'max:255'],
            'ipAddress' => ['required', 'ip'],
            'region' => ['nullable', 'string', 'max:60'],
            'role' => ['required', 'in:web,database,combined,backup'],
            'webServer' => ['required', 'in:nginx,apache'],
            'maxSites' => ['required', 'integer', 'min:1'],
            'sshUser' => ['required', 'string', 'max:60'],
            'sshPort' => ['required', 'integer', 'min:1', 'max:65535'],
            'sshPrivateKey' => ['nullable', 'string'],
        ]);

        Server::create([
            'name' => $this->name,
            'hostname' => $this->hostname,
            'ip_address' => $this->ipAddress,
            'region' => $this->region ?: null,
            'role' => $this->role,
            'status' => 'pending',
            'web_server' => $this->webServer,
            'max_sites' => $this->maxSites,
            'ssh_user' => $this->sshUser,
            'ssh_port' => $this->sshPort,
            'ssh_private_key' => $this->sshPrivateKey ?: null,
        ]);

        $this->showCreate = false;
        $this->dispatch('toast', message: 'Hosting node added.', level: 'success');
    }

    public function confirmDelete(int $serverId): void
    {
        $this->deleteTargetId = $serverId;
    }

    public function delete(): void
    {
        $server = Server::findOrFail($this->deleteTargetId);
        $this->authorize('delete', $server);

        if ($server->current_sites > 0) {
            $this->dispatch('toast', message: 'This node still has websites on it. Migrate or remove them first.', level: 'danger');
            $this->deleteTargetId = null;

            return;
        }

        $server->delete();
        $this->deleteTargetId = null;
        $this->dispatch('toast', message: 'Hosting node removed.', level: 'success');
    }

    public function render()
    {
        return view('livewire.admin.servers-manager', [
            'servers' => Server::with('latestMetric')->orderBy('name')->get(),
        ])->title('Servers · Admin · Portway');
    }
}
