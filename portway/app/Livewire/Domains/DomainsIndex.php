<?php

namespace App\Livewire\Domains;

use App\Models\Domain;
use App\Models\Site;
use App\Services\Dns\DomainConnectionService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Domains')]
class DomainsIndex extends Component
{
    public bool $showAddModal = false;

    public string $hostname = '';

    public ?int $siteId = null;

    public function mount(): void
    {
        $this->siteId = auth()->user()->sites()->value('id');
    }

    public function addDomain(DomainConnectionService $service): void
    {
        $this->validate([
            'hostname' => ['required', 'string', 'max:255'],
            'siteId' => ['required', 'integer'],
        ]);

        $site = Site::where('user_id', auth()->id())->findOrFail($this->siteId);

        try {
            $service->addDomain(auth()->user(), $site, $this->hostname);
            $this->showAddModal = false;
            $this->hostname = '';
            $this->dispatch('toast', message: 'Domain added — check the setup instructions to finish connecting it.', level: 'success');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->setErrorBag($e->errors());
        } catch (\Throwable $e) {
            $this->dispatch('toast', message: $e->getMessage(), level: 'danger');
        }
    }

    public function checkDns(int $domainId, DomainConnectionService $service): void
    {
        $domain = Domain::where('user_id', auth()->id())->findOrFail($domainId);
        $service->checkDns($domain);
        $this->dispatch('toast', message: 'DNS status refreshed.', level: 'info');
    }

    public function render()
    {
        return view('livewire.domains.domains-index', [
            'domains' => Domain::where('user_id', auth()->id())->with(['site', 'sslCertificate'])->latest()->get(),
            'sites' => auth()->user()->sites()->get(),
        ]);
    }
}
