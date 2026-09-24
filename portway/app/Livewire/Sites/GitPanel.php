<?php

namespace App\Livewire\Sites;

use App\Models\Deployment;
use App\Models\GitRepository;
use App\Models\Site;
use App\Services\Deployments\DeploymentService;
use Livewire\Attributes\Locked;
use Livewire\Component;

class GitPanel extends Component
{
    #[Locked]
    public Site $site;

    // Connect form
    public string $url = '';

    public string $branch = 'main';

    // Edit form (existing repository)
    public string $editBranch = '';

    public string $installCommand = '';

    public string $buildCommand = '';

    public bool $autoDeployOnPush = false;

    public bool $showDisconnectConfirm = false;

    public ?int $viewingDeploymentId = null;

    public function mount(Site $site): void
    {
        $this->authorize('view', $site);
        $this->site = $site;

        if ($repository = $site->gitRepository) {
            $this->editBranch = $repository->branch;
            $this->installCommand = (string) $repository->install_command;
            $this->buildCommand = (string) $repository->build_command;
            $this->autoDeployOnPush = (bool) $repository->auto_deploy_on_push;
        }
    }

    public function connect(DeploymentService $deployments): void
    {
        $this->authorize('update', $this->site);

        if ($this->site->gitRepository) {
            return;
        }

        $this->validate([
            'url' => ['required', 'url:http,https,ssh,git', 'max:255'],
            'branch' => ['required', 'string', 'max:100', 'regex:/^[\w\-.\/]+$/', 'not_regex:/^-/'],
        ]);

        try {
            $result = $deployments->checkout($this->site, $this->url, $this->branch);

            if ($result['exit_code'] !== 0) {
                $this->dispatch('toast', message: 'Could not fetch the repository: '.\Illuminate\Support\Str::limit(trim($result['output']), 300), level: 'danger');

                return;
            }
        } catch (\Throwable $e) {
            $this->dispatch('toast', message: $e->getMessage(), level: 'danger');

            return;
        }

        $repository = GitRepository::create([
            'site_id' => $this->site->id,
            'provider' => match (true) {
                str_contains($this->url, 'github.com') => 'github',
                str_contains($this->url, 'gitlab.com') => 'gitlab',
                str_contains($this->url, 'bitbucket.org') => 'bitbucket',
                default => 'generic',
            },
            'url' => $this->url,
            'branch' => $this->branch,
        ]);

        $this->editBranch = $repository->branch;
        $this->url = '';
        $this->dispatch('toast', message: 'Repository connected.', level: 'success');
    }

    public function saveSettings(): void
    {
        $repository = $this->site->gitRepository;

        if (! $repository) {
            return;
        }

        $this->authorize('update', $repository);

        $safeCommand = function (string $attribute, mixed $value, \Closure $fail) {
            if (filled($value) && ! \App\Services\Provisioning\CommandSanitizer::isSafe((string) $value)) {
                $fail('That command is not allowed by the security sandbox.');
            }
        };

        $this->validate([
            'editBranch' => ['required', 'string', 'max:100', 'regex:/^[\w\-.\/]+$/', 'not_regex:/^-/'],
            'installCommand' => ['nullable', 'string', 'max:255', $safeCommand],
            'buildCommand' => ['nullable', 'string', 'max:255', $safeCommand],
        ]);

        $repository->update([
            'branch' => $this->editBranch,
            'install_command' => $this->installCommand ?: null,
            'build_command' => $this->buildCommand ?: null,
            'auto_deploy_on_push' => $this->autoDeployOnPush,
        ]);

        $this->dispatch('toast', message: 'Deployment settings saved.', level: 'success');
    }

    public function deployNow(DeploymentService $service): void
    {
        $repository = $this->site->gitRepository;

        if (! $repository) {
            return;
        }

        $this->authorize('deploy', $repository);

        $deployment = $service->deploy($repository, 'manual', auth()->user());

        $status = $deployment->fresh()->status;
        $this->dispatch('toast', ...match ($status) {
            'succeeded' => ['message' => 'Deployment finished.', 'level' => 'success'],
            'failed' => ['message' => 'Deployment failed — open its log for details.', 'level' => 'danger'],
            default => ['message' => 'Deployment queued.', 'level' => 'success'],
        });
    }

    public function confirmDisconnect(): void
    {
        $this->showDisconnectConfirm = true;
    }

    public function disconnect(): void
    {
        $repository = $this->site->gitRepository;

        if (! $repository) {
            return;
        }

        $this->authorize('delete', $repository);

        $repository->delete();
        $this->showDisconnectConfirm = false;
        $this->url = '';
        $this->editBranch = '';
        $this->installCommand = '';
        $this->buildCommand = '';
        $this->autoDeployOnPush = false;

        $this->dispatch('toast', message: 'Repository disconnected.', level: 'success');
    }

    public function viewLog(int $deploymentId): void
    {
        $this->viewingDeploymentId = $deploymentId;
    }

    public function closeLog(): void
    {
        $this->viewingDeploymentId = null;
    }

    public function render()
    {
        $this->site->refresh();

        return view('livewire.sites.git-panel', [
            'repository' => $this->site->gitRepository,
            'deployments' => $this->site->deployments()->latest()->limit(15)->get(),
            'viewingDeployment' => $this->viewingDeploymentId
                ? Deployment::where('site_id', $this->site->id)->find($this->viewingDeploymentId)
                : null,
        ]);
    }
}
