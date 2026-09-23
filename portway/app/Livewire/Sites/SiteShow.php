<?php

namespace App\Livewire\Sites;

use App\Models\Site;
use App\Services\Provisioning\ProvisionerDriver;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SiteShow extends Component
{
    public Site $site;

    public string $tab = 'overview';

    public const TABS = [
        'overview' => 'Overview',
        'domains' => 'Domains',
        'files' => 'Files',
        'database' => 'Database',
        'php' => 'PHP',
        'nodejs' => 'Node.js',
        'ssl' => 'SSL',
        'dns' => 'DNS',
        'git' => 'Git',
        'backups' => 'Backups',
        'cron' => 'Cron',
        'terminal' => 'Terminal',
        'logs' => 'Logs',
        'security' => 'Security',
        'settings' => 'Settings',
    ];

    public function mount(Site $site, ?string $tab = null): void
    {
        $this->authorize('view', $site);
        $this->site = $site;
        $this->tab = array_key_exists($tab, self::TABS) ? $tab : 'overview';
    }

    public function setTab(string $tab): void
    {
        $this->tab = array_key_exists($tab, self::TABS) ? $tab : 'overview';
    }

    public function savePhpSettings(ProvisionerDriver $driver): void
    {
        $this->authorize('update', $this->site);

        $this->validate([
            'site.php_version' => ['required', 'in:'.implode(',', config('portway.php_versions'))],
            'site.php_memory_limit_mb' => ['required', 'integer', 'min:64', 'max:2048'],
            'site.php_upload_max_mb' => ['required', 'integer', 'min:1', 'max:1024'],
            'site.php_max_execution_seconds' => ['required', 'integer', 'min:5', 'max:300'],
        ]);

        $this->site->save();
        $driver->setPhpVersion($this->site, $this->site->php_version);
        $driver->writeVirtualHost($this->site);
        $driver->reloadWebServer($this->site);

        $this->dispatch('toast', message: 'PHP settings updated.', level: 'success');
    }

    public function saveNodeSettings(ProvisionerDriver $driver): void
    {
        $this->authorize('update', $this->site);

        $this->validate([
            'site.node_version' => ['required', 'in:'.implode(',', config('portway.node_versions'))],
            'site.node_install_command' => ['nullable', 'string', 'max:255'],
            'site.node_build_command' => ['nullable', 'string', 'max:255'],
            'site.node_start_command' => ['nullable', 'string', 'max:255'],
        ]);

        $this->site->save();
        $this->dispatch('toast', message: 'Node.js settings updated. Restart the app to apply.', level: 'success');
    }

    public function restartNode(ProvisionerDriver $driver): void
    {
        $driver->restartNodeProcess($this->site);
        $this->dispatch('toast', message: 'Node.js process restarted.', level: 'success');
    }

    public function saveGeneralSettings(): void
    {
        $this->authorize('update', $this->site);

        $this->validate([
            'site.name' => ['required', 'string', 'min:2', 'max:60'],
            'site.document_root' => ['required', 'string', 'max:100'],
        ]);

        $this->site->save();
        $this->dispatch('toast', message: 'Website settings updated.', level: 'success');
    }

    public function toggleForceHttps(): void
    {
        $this->authorize('update', $this->site);
        $this->site->update(['force_https' => ! $this->site->force_https]);
    }

    public function relevantTabs(): array
    {
        $tabs = self::TABS;

        if ($this->site->runtime !== 'php') {
            unset($tabs['php']);
        }

        if ($this->site->runtime !== 'node') {
            unset($tabs['nodejs']);
        }

        return $tabs;
    }

    public function render()
    {
        return view('livewire.sites.site-show', [
            'tabs' => $this->relevantTabs(),
        ])->title("{$this->site->name} · Portway");
    }
}
