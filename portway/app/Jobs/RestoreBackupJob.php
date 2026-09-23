<?php

namespace App\Jobs;

use App\Enums\BackupStatus;
use App\Models\Backup;
use App\Services\Provisioning\ProvisionerDriver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RestoreBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;

    public function __construct(public Backup $backup)
    {
    }

    public function handle(ProvisionerDriver $driver): void
    {
        try {
            $driver->restoreBackupArchive($this->backup);
            $this->backup->update(['status' => BackupStatus::Restored]);

            $this->backup->site->update(['disk_usage_bytes' => $driver->diskUsageBytes($this->backup->site)]);
        } catch (Throwable $e) {
            $this->backup->update(['status' => BackupStatus::Failed, 'failure_reason' => 'Restore failed: '.$e->getMessage()]);
            throw $e;
        }
    }
}
