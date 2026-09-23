<?php

namespace App\Livewire\Sites;

use App\Models\Site;
use App\Services\Provisioning\SiteProvisioningService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Websites')]
class SitesIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public ?string $tool = null;

    public ?int $confirmingDeleteId = null;

    public function mount(): void
    {
        $this->tool = request()->query('tool');
    }

    public function confirmDelete(int $siteId): void
    {
        $this->confirmingDeleteId = $siteId;
    }

    public function delete(SiteProvisioningService $service): void
    {
        $site = Site::where('user_id', auth()->id())->findOrFail($this->confirmingDeleteId);
        $this->authorize('delete', $site);

        $service->delete($site);
        $this->confirmingDeleteId = null;

        $this->dispatch('toast', message: "Deleting {$site->name}…", level: 'info');
    }

    public function toolLabel(): string
    {
        return match ($this->tool) {
            'files' => 'Open Files',
            'terminal' => 'Open Terminal',
            'git' => 'Open Git',
            'cron' => 'Open Cron Jobs',
            'logs' => 'Open Logs',
            'backups' => 'Open Backups',
            default => 'Manage',
        };
    }

    public function toolTab(): ?string
    {
        return match ($this->tool) {
            'files' => 'files',
            'terminal' => 'terminal',
            'git' => 'git',
            'cron' => 'cron',
            'logs' => 'logs',
            'backups' => 'backups',
            default => null,
        };
    }

    public function render()
    {
        $sites = Site::where('user_id', auth()->id())
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->with('domains')
            ->latest()
            ->paginate(10);

        return view('livewire.sites.sites-index', [
            'sites' => $sites,
            'toolLabel' => $this->toolLabel(),
            'toolTab' => $this->toolTab(),
        ]);
    }
}
