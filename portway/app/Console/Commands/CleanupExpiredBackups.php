<?php

namespace App\Console\Commands;

use App\Models\Backup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupExpiredBackups extends Command
{
    protected $signature = 'backup:cleanup-expired';

    protected $description = 'Delete backup archives past their expiry or beyond the per-site retention count.';

    public function handle(): int
    {
        $expired = Backup::query()->whereNotNull('expires_at')->where('expires_at', '<', now())->get();

        foreach ($expired as $backup) {
            $this->deleteBackup($backup);
        }

        $this->info("Removed {$expired->count()} expired backup(s).");

        Backup::query()->select('site_id')->distinct()->pluck('site_id')->each(function ($siteId) {
            $keep = config('portway.backups.max_per_site');

            Backup::where('site_id', $siteId)
                ->where('status', 'completed')
                ->orderByDesc('completed_at')
                ->skip($keep)
                ->take(1000)
                ->get()
                ->each(fn (Backup $backup) => $this->deleteBackup($backup));
        });

        return self::SUCCESS;
    }

    private function deleteBackup(Backup $backup): void
    {
        if ($backup->path) {
            Storage::disk($backup->disk)->delete($backup->path);
        }

        $backup->delete();
    }
}
