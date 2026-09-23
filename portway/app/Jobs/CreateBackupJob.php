<?php

namespace App\Jobs;

use App\Enums\BackupStatus;
use App\Events\BackupCompleted;
use App\Models\Backup;
use App\Services\Provisioning\ProvisionerDriver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CreateBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;

    public function __construct(public Backup $backup)
    {
    }

    public function handle(ProvisionerDriver $driver): void
    {
        $this->backup->update(['status' => BackupStatus::Running, 'started_at' => now()]);

        try {
            $path = $driver->createBackupArchive($this->backup);
            $size = Storage::disk($this->backup->disk)->size($path);

            $this->backup->update([
                'status' => BackupStatus::Completed,
                'path' => $path,
                'size_bytes' => $size,
                'completed_at' => now(),
            ]);

            BackupCompleted::dispatch($this->backup->fresh());
        } catch (Throwable $e) {
            $this->backup->update([
                'status' => BackupStatus::Failed,
                'failure_reason' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
