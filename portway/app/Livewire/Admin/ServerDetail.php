<?php

namespace App\Livewire\Admin;

use App\Models\Server;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ServerDetail extends Component
{
    public Server $server;

    public string $status = 'online';

    public int $maxSites = 0;

    public bool $confirmingDelete = false;

    public function mount(Server $server): void
    {
        $this->authorize('view', $server);
        $this->server = $server;
        $this->status = $server->status;
        $this->maxSites = $server->max_sites;
    }

    public function save(): void
    {
        $this->authorize('update', $this->server);

        $this->validate([
            'status' => ['required', 'in:pending,online,degraded,offline,maintenance'],
            'maxSites' => ['required', 'integer', 'min:1'],
        ]);

        $this->server->update([
            'status' => $this->status,
            'max_sites' => $this->maxSites,
        ]);

        $this->dispatch('toast', message: 'Hosting node updated.', level: 'success');
    }

    public function confirmDelete(): void
    {
        $this->confirmingDelete = true;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->server);

        if ($this->server->current_sites > 0) {
            $this->dispatch('toast', message: 'This node still has websites on it.', level: 'danger');
            $this->confirmingDelete = false;

            return;
        }

        $this->server->delete();
        $this->redirectRoute('admin.servers.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.server-detail', [
            'metrics' => $this->server->metrics()->latest('recorded_at')->limit(30)->get()->reverse(),
            'sites' => $this->server->sites()->with('user')->latest()->limit(20)->get(),
            'health' => $this->server->latestMetric?->healthStatus(),
        ])->title("{$this->server->name} · Admin · Portway");
    }
}
