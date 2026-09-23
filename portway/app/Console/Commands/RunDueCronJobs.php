<?php

namespace App\Console\Commands;

use App\Jobs\ExecuteCronJobJob;
use App\Models\CronJob;
use Cron\CronExpression;
use Illuminate\Console\Command;

class RunDueCronJobs extends Command
{
    protected $signature = 'portway:run-cron-jobs';

    protected $description = "Dispatch every user cron job whose schedule is due (Portway's own scheduler tick, run every minute).";

    public function handle(): int
    {
        $dispatched = 0;

        CronJob::query()->where('is_active', true)->each(function (CronJob $cronJob) use (&$dispatched) {
            try {
                $expression = new CronExpression($cronJob->schedule);
            } catch (\Throwable) {
                return;
            }

            $reference = $cronJob->last_run_at ?? now()->subMinute();

            if ($expression->isDue(now()) || $expression->getNextRunDate($reference)->lessThanOrEqualTo(now())) {
                ExecuteCronJobJob::dispatch($cronJob)->onQueue('default');
                $dispatched++;
            }
        });

        $this->info("Dispatched {$dispatched} due cron job(s).");

        return self::SUCCESS;
    }
}
