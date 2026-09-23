<?php

namespace App\Jobs;

use App\Events\DeploymentFinished;
use App\Models\Deployment;
use App\Services\Provisioning\ProvisionerDriver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class DeploySiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(public Deployment $deployment)
    {
    }

    public function handle(ProvisionerDriver $driver): void
    {
        $this->deployment->update(['status' => 'running', 'started_at' => now()]);
        $repository = $this->deployment->gitRepository;
        $site = $this->deployment->site;

        $steps = [
            sprintf('git fetch origin %s && git reset --hard origin/%s', $this->deployment->branch, $this->deployment->branch),
        ];

        if ($repository->install_command) {
            $steps[] = $repository->install_command;
        }

        if ($repository->build_command) {
            $steps[] = $repository->build_command;
        }

        try {
            foreach ($steps as $command) {
                $result = $driver->runCommand($site, $command, timeoutSeconds: 280);
                $this->deployment->appendLog("$ {$command}\n{$result['output']}");

                if ($result['exit_code'] !== 0) {
                    throw new \RuntimeException("Command failed ({$result['exit_code']}): {$command}");
                }
            }

            $commitResult = $driver->runCommand($site, 'git rev-parse HEAD', timeoutSeconds: 15);
            $commitSha = trim($commitResult['output']);

            $this->deployment->update([
                'status' => 'succeeded',
                'commit_sha' => $commitSha ?: null,
                'finished_at' => now(),
            ]);

            $repository->update(['last_commit_sha' => $commitSha ?: null, 'last_deployed_at' => now()]);
            $site->update(['last_deployed_at' => now()]);

            if ($site->runtime === 'node') {
                $driver->restartNodeProcess($site);
            }
        } catch (Throwable $e) {
            $this->deployment->appendLog('ERROR: '.$e->getMessage());
            $this->deployment->update(['status' => 'failed', 'finished_at' => now()]);
        }

        DeploymentFinished::dispatch($this->deployment->fresh());
    }
}
