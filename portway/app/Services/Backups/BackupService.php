<?php

namespace App\Services\Backups;

use App\Enums\BackupStatus;
use App\Exceptions\QuotaExceededException;
use App\Jobs\CreateBackupJob;
use App\Jobs\RestoreBackupJob;
use App\Models\Backup;
use App\Models\Site;
use App\Models\User;

class BackupService
{
    public function create(User $user, Site $site, string $type = 'full', string $trigger = 'manual'): Backup
    {
        $existing = Backup::where('site_id', $site->id)->where('status', '!=', BackupStatus::Failed)->count();

        if ($existing >= $user->max_backups) {
            throw QuotaExceededException::backupLimit($user->max_backups);
        }

        $backup = Backup::create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'initiated_by' => auth()->id(),
            'type' => $type,
            'trigger' => $trigger,
            'status' => BackupStatus::Queued,
            'expires_at' => now()->addDays(config('portway.backups.retention_days')),
        ]);

        CreateBackupJob::dispatch($backup)->onQueue(config('queue.names.backups'));

        return $backup;
    }

    public function restore(Backup $backup): void
    {
        $backup->update(['status' => BackupStatus::Restoring]);
        RestoreBackupJob::dispatch($backup)->onQueue(config('queue.names.backups'));
    }

    public function delete(Backup $backup): void
    {
        if ($backup->path) {
            \Illuminate\Support\Facades\Storage::disk($backup->disk)->delete($backup->path);
        }

        $backup->delete();
    }
}
