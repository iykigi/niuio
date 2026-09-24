<?php

namespace App\Services\Deployments;

use App\Jobs\DeploySiteJob;
use App\Models\Deployment;
use App\Models\GitRepository;
use App\Models\Site;
use App\Models\User;
use App\Services\Provisioning\ProvisionerDriver;

class DeploymentService
{
    /**
     * Checks a repository out into the site's (usually non-empty) document
     * root. `git clone <url> .` refuses to run in a folder that already has
     * files — every new site has at least the starter index file — so the
     * repository is initialised in place and the branch force-checked-out
     * over whatever is there.
     *
     * @return array{exit_code: int, output: string}
     */
    public function checkout(Site $site, string $url, string $branch): array
    {
        $driver = app(ProvisionerDriver::class);
        $output = '';

        $run = function (string $command) use ($driver, $site, &$output): int {
            $result = $driver->runCommand($site, $command, timeoutSeconds: 180);
            $output .= "$ {$command}\n".trim($result['output'])."\n";

            return $result['exit_code'];
        };

        $run('git init -q');

        // Re-connecting after a disconnect: the old "origin" is still there.
        if ($run('git remote add origin '.escapeshellarg($url)) !== 0) {
            $run('git remote set-url origin '.escapeshellarg($url));
        }

        foreach ([
            'git fetch --depth 1 origin '.escapeshellarg($branch),
            'git checkout -q -f -B '.escapeshellarg($branch).' FETCH_HEAD',
        ] as $command) {
            if (($exitCode = $run($command)) !== 0) {
                return ['exit_code' => $exitCode, 'output' => $output];
            }
        }

        return ['exit_code' => 0, 'output' => $output];
    }

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
