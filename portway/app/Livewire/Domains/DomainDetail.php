<?php

namespace App\Livewire\Domains;

use App\Models\DnsRecord;
use App\Models\Domain;
use App\Services\Dns\DomainConnectionService;
use App\Services\Provisioning\ProvisionerDriver;
use App\Services\Ssl\SslService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class DomainDetail extends Component
{
    public Domain $domain;

    public string $tab = 'dns';

    public bool $showAddRecord = false;

    public string $recordType = 'A';

    public string $recordName = '@';

    public string $recordContent = '';

    public int $recordTtl = 3600;

    public ?int $recordPriority = null;

    public function mount(Domain $domain, ?string $tab = null): void
    {
        $this->authorize('view', $domain);
        $this->domain = $domain;
        $this->tab = in_array($tab, ['dns', 'ssl', 'settings'], true) ? $tab : 'dns';
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['dns', 'ssl', 'settings'], true) ? $tab : 'dns';
    }

    public function checkDns(DomainConnectionService $service): void
    {
        $this->domain = $service->checkDns($this->domain);
        $this->dispatch('toast', message: 'DNS status refreshed.', level: 'info');
    }

    public function addRecord(ProvisionerDriver $driver): void
    {
        $this->authorize('update', $this->domain);

        $this->validate([
            'recordType' => [Rule::in(['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'SRV', 'CAA'])],
            'recordName' => ['required', 'string', 'max:255'],
            'recordContent' => ['required', 'string', 'max:2000'],
            'recordTtl' => ['required', 'integer', 'min:60', 'max:86400'],
        ]);

        $record = DnsRecord::create([
            'domain_id' => $this->domain->id,
            'type' => $this->recordType,
            'name' => $this->recordName,
            'content' => $this->recordContent,
            'ttl' => $this->recordTtl,
            'priority' => $this->recordPriority,
            'status' => 'pending',
        ]);

        try {
            $driver->applyDnsRecord($this->domain, $record->type, $record->name, $record->content, $record->ttl);
            $record->update(['status' => 'active']);
        } catch (\Throwable $e) {
            report($e);
            $record->update(['status' => 'failed']);
        }

        $this->showAddRecord = false;
        $this->reset(['recordName', 'recordContent', 'recordPriority']);
        $this->dispatch('toast', message: 'DNS record added.', level: 'success');
    }

    public function deleteRecord(int $recordId, ProvisionerDriver $driver): void
    {
        $this->authorize('update', $this->domain);

        $record = DnsRecord::where('domain_id', $this->domain->id)->findOrFail($recordId);
        $driver->removeDnsRecord($this->domain, $record->type, $record->name);
        $record->delete();
        $this->dispatch('toast', message: 'DNS record removed.', level: 'success');
    }

    public function issueSsl(SslService $sslService): void
    {
        $this->authorize('update', $this->domain);

        try {
            $sslService->issueForDomain($this->domain);
            $this->dispatch('toast', message: 'SSL certificate issued.', level: 'success');
        } catch (\Throwable $e) {
            $this->dispatch('toast', message: "SSL issuance failed: {$e->getMessage()}", level: 'danger');
        }
    }

    public function toggleForceHttps(): void
    {
        $this->authorize('update', $this->domain);
        $this->domain->update(['force_https' => ! $this->domain->force_https]);
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->domain);
        $this->domain->delete();
        $this->redirectRoute('domains.index', navigate: true);
    }

    public function render(DomainConnectionService $service)
    {
        return view('livewire.domains.domain-detail', [
            'expectedRecords' => $service->expectedRecords($this->domain),
            'dnsRecords' => $this->domain->dnsRecords()->orderBy('type')->get(),
        ]);
    }
}
