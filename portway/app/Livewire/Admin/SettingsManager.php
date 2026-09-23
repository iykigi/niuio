<?php

namespace App\Livewire\Admin;

use App\Models\SystemSetting;
use App\Services\Settings;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SettingsManager extends Component
{
    // Quotas
    public int $defaultStorageQuotaMb = 10240;

    public int $defaultMaxWebsites = 5;

    public int $defaultMaxDatabases = 5;

    public bool $backupsCountTowardQuota = false;

    // Runtimes
    public string $availablePhpVersions = '';

    public string $availableNodeVersions = '';

    // Features
    public bool $registrationOpen = true;

    public bool $emailHostingEnabled = false;

    public bool $malwareScanningEnabled = false;

    // Network
    public string $serverIp = '';

    public string $temporaryDomainSuffix = '';

    public function mount(Settings $settings): void
    {
        $this->authorize('settings.view');

        $this->defaultStorageQuotaMb = (int) $settings->get('default_storage_quota_mb', config('portway.defaults.storage_quota_mb'));
        $this->defaultMaxWebsites = (int) $settings->get('default_max_websites', config('portway.defaults.max_websites'));
        $this->defaultMaxDatabases = (int) $settings->get('default_max_databases', config('portway.defaults.max_databases'));
        $this->backupsCountTowardQuota = (bool) $settings->get('backups_count_toward_quota', false);

        $this->availablePhpVersions = implode(', ', (array) $settings->get('available_php_versions', config('portway.php_versions')));
        $this->availableNodeVersions = implode(', ', (array) $settings->get('available_node_versions', config('portway.node_versions')));

        $this->registrationOpen = (bool) $settings->get('registration_open', true);
        $this->emailHostingEnabled = (bool) $settings->get('email_hosting_enabled', false);
        $this->malwareScanningEnabled = (bool) $settings->get('malware_scanning_enabled', false);

        $this->serverIp = (string) $settings->get('server_ip', config('portway.server_ip'));
        $this->temporaryDomainSuffix = (string) $settings->get('temporary_domain_suffix', config('portway.temporary_domain_suffix'));
    }

    public function saveQuotas(Settings $settings): void
    {
        $this->authorize('settings.manage');

        $this->validate([
            'defaultStorageQuotaMb' => ['required', 'integer', 'min:0'],
            'defaultMaxWebsites' => ['required', 'integer', 'min:0'],
            'defaultMaxDatabases' => ['required', 'integer', 'min:0'],
        ]);

        $settings->set('default_storage_quota_mb', $this->defaultStorageQuotaMb, Auth::user(), 'quotas');
        $settings->set('default_max_websites', $this->defaultMaxWebsites, Auth::user(), 'quotas');
        $settings->set('default_max_databases', $this->defaultMaxDatabases, Auth::user(), 'quotas');
        $settings->set('backups_count_toward_quota', $this->backupsCountTowardQuota, Auth::user(), 'quotas');

        $this->dispatch('toast', message: 'Default quotas saved. New signups will use these values.', level: 'success');
    }

    public function saveRuntimes(Settings $settings): void
    {
        $this->authorize('settings.manage');

        $php = array_values(array_filter(array_map('trim', explode(',', $this->availablePhpVersions))));
        $node = array_values(array_filter(array_map('trim', explode(',', $this->availableNodeVersions))));

        $settings->set('available_php_versions', $php, Auth::user(), 'runtimes');
        $settings->set('available_node_versions', $node, Auth::user(), 'runtimes');

        $this->dispatch('toast', message: 'Available runtimes saved.', level: 'success');
    }

    public function saveFeatures(Settings $settings): void
    {
        $this->authorize('settings.manage');

        $settings->set('registration_open', $this->registrationOpen, Auth::user(), 'features');
        $settings->set('email_hosting_enabled', $this->emailHostingEnabled, Auth::user(), 'features');
        $settings->set('malware_scanning_enabled', $this->malwareScanningEnabled, Auth::user(), 'features');

        $this->dispatch('toast', message: 'Feature flags saved.', level: 'success');
    }

    public function saveNetwork(Settings $settings): void
    {
        $this->authorize('settings.manage');

        $this->validate([
            'serverIp' => ['required', 'ip'],
            'temporaryDomainSuffix' => ['required', 'string', 'max:100'],
        ]);

        $settings->set('server_ip', $this->serverIp, Auth::user(), 'network');
        $settings->set('temporary_domain_suffix', $this->temporaryDomainSuffix, Auth::user(), 'network');

        $this->dispatch('toast', message: 'Network settings saved.', level: 'success');
    }

    public function render()
    {
        return view('livewire.admin.settings-manager', [
            'lastUpdated' => SystemSetting::query()->latest('updated_at')->first(),
        ])->title('Settings · Admin · Portway');
    }
}
