<?php

namespace App\Services\Deployments;

use App\Jobs\DeploySiteJob;
use App\Models\Deployment;
use App\Models\GitRepository;
use App\Models\User;

class DeploymentService
{
    public function deploy(GitRepository $repository, string $trigger = 'manual', ?User $triggeredBy = null): Deployment
    {
        $deployment = Deployment::create([
            'site_id' => $repository->site_id,
            'git_repository_id' => $repository->id,
            'triggered_by' => $triggeredBy?->id,
            'trigger' => $trigger,
            'branch' => $repository->branch,
            'status' => 'queued',
        ]);

        DeploySiteJob::dispatch($deployment)->onQueue(config('queue.names.deployments'));

        return $deployment;
    }
}
