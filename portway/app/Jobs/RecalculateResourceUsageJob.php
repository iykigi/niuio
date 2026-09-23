<?php

namespace App\Jobs;

use App\Events\StorageQuotaThresholdReached;
use App\Models\ResourceUsage;
use App\Models\Site;
use App\Models\User;
use App\Services\Provisioning\ProvisionerDriver;
use App\Services\Storage\StorageUsageCalculator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Refreshes every site's on-disk usage from the provisioner driver,
 * writes today's resource_usage row per account, and fires a storage
 * warning the first time an account crosses 80/90/95/100% in a day.
 */
class RecalculateResourceUsageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(ProvisionerDriver $driver, StorageUsageCalculator $calculator): void
    {
        Site::query()->whereNotNull('id')->each(function (Site $site) use ($driver) {
            $site->update([
                'disk_usage_bytes' => $driver->diskUsageBytes($site),
                'disk_usage_calculated_at' => now(),
            ]);
        });

        User::query()->each(function (User $user) use ($calculator) {
            $calculator->forget($user);

            ResourceUsage::updateOrCreate(
                ['user_id' => $user->id, 'site_id' => null, 'date' => now()->toDateString()],
                ['storage_bytes' => $calculator->usedBytes($user)]
            );

            $threshold = $calculator->highestCrossedThreshold($user);
            $cacheKey = "portway:storage_warning_sent:{$user->id}:".now()->toDateString().":{$threshold}";

            if ($threshold && ! cache()->has($cacheKey)) {
                StorageQuotaThresholdReached::dispatch($user, $threshold);
                cache()->put($cacheKey, true, now()->endOfDay());
            }
        });
    }
}
