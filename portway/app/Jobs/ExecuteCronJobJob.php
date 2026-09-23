<?php

namespace App\Jobs;

use App\Models\CronJob;
use App\Services\Provisioning\CommandSanitizer;
use App\Services\Provisioning\ProvisionerDriver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteCronJobJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function __construct(public CronJob $cronJob)
    {
    }

    public function handle(ProvisionerDriver $driver): void
    {
        if (! CommandSanitizer::isSafe($this->cronJob->command)) {
            $this->cronJob->update([
                'last_run_at' => now(),
                'last_exit_code' => 126,
                'last_output' => 'Command blocked by the sandbox allowlist. Edit the cron job to fix it.',
            ]);

            return;
        }

        $result = $driver->runCommand($this->cronJob->site, $this->cronJob->command, timeoutSeconds: 280);

        $this->cronJob->update([
            'last_run_at' => now(),
            'last_exit_code' => $result['exit_code'],
            'last_output' => mb_substr($result['output'], 0, 10000),
        ]);
    }
}
