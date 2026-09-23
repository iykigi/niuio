<?php

namespace App\Livewire\Sites;

use App\Models\Site;
use App\Services\Provisioning\SiteProvisioningService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Create Website')]
class CreateSite extends Component
{
    public int $step = 1;

    // Step 1
    public string $projectType = '';

    // Step 2
    public string $name = '';

    // Step 3
    public string $domainChoice = 'temporary'; // temporary | custom
    public string $customDomain = '';

    // Step 4
    public string $phpVersion = '';
    public string $nodeVersion = '';
    public string $gitUrl = '';
    public string $gitBranch = 'main';

    public ?int $createdSiteId = null;

    public function mount(): void
    {
        $this->phpVersion = config('portway.default_php_version');
        $this->nodeVersion = config('portway.default_node_version');
    }

    public function selectProjectType(string $type): void
    {
        $this->projectType = $type;
        $this->step = 2;
    }

    public function nextStep(): void
    {
        if ($this->step === 2) {
            $this->validate(['name' => ['required', 'string', 'min:2', 'max:60']]);
        }

        if ($this->step === 3 && $this->domainChoice === 'custom') {
            $this->validate(['customDomain' => ['required', 'string', 'max:255']]);
        }

        $this->step = min(4, $this->step + 1);
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function create(SiteProvisioningService $service): void
    {
        $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:60'],
            'projectType' => ['required', 'string'],
        ]);

        $recipe = config("portway.project_types.{$this->projectType}");

        $site = $service->create(auth()->user(), [
            'name' => $this->name,
            'project_type' => $this->projectType,
            'php_version' => $recipe['runtime'] === 'php' ? $this->phpVersion : null,
            'node_version' => $recipe['runtime'] === 'node' ? $this->nodeVersion : null,
            'git_url' => $this->projectType === 'git' ? $this->gitUrl : null,
            'git_branch' => $this->gitBranch,
        ]);

        $this->createdSiteId = $site->id;
        $this->step = 5; // progress screen
    }

    public function getSiteProperty(): ?Site
    {
        return $this->createdSiteId ? Site::find($this->createdSiteId) : null;
    }

    public function render()
    {
        return view('livewire.sites.create-site', [
            'projectTypes' => config('portway.project_types'),
            'phpVersions' => config('portway.php_versions'),
            'nodeVersions' => config('portway.node_versions'),
            'site' => $this->getSiteProperty(),
        ]);
    }
}
