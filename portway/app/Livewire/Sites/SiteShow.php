<?php

namespace App\Livewire\Sites;

use App\Models\Site;
use App\Services\Provisioning\CommandSanitizer;
use App\Services\Provisioning\ProvisionerDriver;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SiteShow extends Component
{
    public Site $site;

    public string $tab = 'overview';

    // Editable copies of the site's settings. Livewire 3 does not bind
    // wire:model straight into Eloquent attributes ("site.php_version"),
    // so the tabs bind to these and the save actions copy them back.
    public string $name = '';

    public string $documentRoot = '';

    public ?string $phpVersion = null;

    public ?int $phpMemoryLimitMb = null;

    public ?int $phpUploadMaxMb = null;

    public ?int $phpMaxExecutionSeconds = null;

    public ?string $nodeVersion = null;

    public ?string $nodeInstallCommand = null;

    public ?string $nodeBuildCommand = null;

    public ?string $nodeStartCommand = null;

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
        $this->fillFormFromSite();
    }

    private function fillFormFromSite(): void
    {
        $this->name = (string) $this->site->name;
        $this->documentRoot = (string) $this->site->document_root;
        $this->phpVersion = $this->site->php_version ?? config('portway.default_php_version');
        $this->phpMemoryLimitMb = $this->site->php_memory_limit_mb;
        $this->phpUploadMaxMb = $this->site->php_upload_max_mb;
        $this->phpMaxExecutionSeconds = $this->site->php_max_execution_seconds;
        $this->nodeVersion = $this->site->node_version ?? config('portway.default_node_version');
        $this->nodeInstallCommand = $this->site->node_install_command;
        $this->nodeBuildCommand = $this->site->node_build_command;
        $this->nodeStartCommand = $this->site->node_start_command;
    }

    /**
     * Node commands are run on the hosting node by the process manager, so
     * they go through the same allowlist as terminal and cron commands.
     */
    private function safeCommandRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) {
            if (filled($value) && ! CommandSanitizer::isSafe((string) $value)) {
                $fail('That command is not allowed. Only php, composer, node, npm, git and common file tools can be used.');
            }
        };
    }

    public function setTab(string $tab): void
    {
        $this->tab = array_key_exists($tab, self::TABS) ? $tab : 'overview';
    }

    public function savePhpSettings(ProvisionerDriver $driver): void
    {
        $this->authorize('update', $this->site);

        $this->validate([
            'phpVersion' => ['required', 'in:'.implode(',', config('portway.php_versions'))],
            'phpMemoryLimitMb' => ['required', 'integer', 'min:64', 'max:2048'],
            'phpUploadMaxMb' => ['required', 'integer', 'min:1', 'max:1024'],
            'phpMaxExecutionSeconds' => ['required', 'integer', 'min:5', 'max:300'],
        ]);

        $this->site->update([
            'php_version' => $this->phpVersion,
            'php_memory_limit_mb' => $this->phpMemoryLimitMb,
            'php_upload_max_mb' => $this->phpUploadMaxMb,
            'php_max_execution_seconds' => $this->phpMaxExecutionSeconds,
        ]);
        $driver->setPhpVersion($this->site, $this->site->php_version);
        $driver->writeVirtualHost($this->site);
        $driver->reloadWebServer($this->site);

        $this->dispatch('toast', message: 'PHP settings updated.', level: 'success');
    }

    public function saveNodeSettings(ProvisionerDriver $driver): void
    {
        $this->authorize('update', $this->site);

        $this->validate([
            'nodeVersion' => ['required', 'in:'.implode(',', config('portway.node_versions'))],
            'nodeInstallCommand' => ['nullable', 'string', 'max:255', $this->safeCommandRule()],
            'nodeBuildCommand' => ['nullable', 'string', 'max:255', $this->safeCommandRule()],
            'nodeStartCommand' => ['nullable', 'string', 'max:255', $this->safeCommandRule()],
        ]);

        $this->site->update([
            'node_version' => $this->nodeVersion,
            'node_install_command' => $this->nodeInstallCommand ?: null,
            'node_build_command' => $this->nodeBuildCommand ?: null,
            'node_start_command' => $this->nodeStartCommand ?: null,
        ]);
        $this->dispatch('toast', message: 'Node.js settings updated. Restart the app to apply.', level: 'success');
    }

    public function restartNode(ProvisionerDriver $driver): void
    {
        $this->authorize('update', $this->site);

        $driver->restartNodeProcess($this->site);
        $this->dispatch('toast', message: 'Node.js process restarted.', level: 'success');
    }

    public function saveGeneralSettings(): void
    {
        $this->authorize('update', $this->site);

        $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:60'],
            // A relative folder inside the site, e.g. "public" or "web/dist" —
            // never an absolute path or one that climbs out with "..".
            'documentRoot' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._\/-]+$/', 'not_regex:/(^|\/)\.\.(\/|$)/'],
        ], [
            'documentRoot.regex' => 'Use a folder inside your website, e.g. "public".',
            'documentRoot.not_regex' => 'The document root cannot point outside your website.',
        ]);

        $this->site->update([
            'name' => $this->name,
            'document_root' => trim($this->documentRoot, '/') ?: 'public',
        ]);

        app(ProvisionerDriver::class)->writeVirtualHost($this->site);
        app(ProvisionerDriver::class)->reloadWebServer($this->site);
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
