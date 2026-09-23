<?php

namespace App\Services\Storage;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Computes an account's total storage usage across every quota
 * category (website files, databases, email, and optionally backups —
 * see config('portway.quota_categories')), and answers the questions
 * the dashboard's storage bar and the upload-blocking guard need.
 */
class StorageUsageCalculator
{
    private const CACHE_TTL_SECONDS = 60;

    public function usedBytes(User $user): int
    {
        return Cache::remember("portway:storage_used:{$user->id}", self::CACHE_TTL_SECONDS, function () use ($user) {
            $total = 0;
            $categories = config('portway.quota_categories');

            if ($categories['websites'] ?? true) {
                $total += (int) $user->sites()->sum('disk_usage_bytes');
            }

            if ($categories['databases'] ?? true) {
                $total += (int) $user->databases()->sum('size_bytes');
            }

            if ($categories['email'] ?? true) {
                $total += (int) \App\Models\EmailAccount::whereIn(
                    'domain_id',
                    $user->domains()->pluck('id')
                )->sum('used_bytes');
            }

            if ($categories['backups'] ?? false) {
                $total += (int) \App\Models\Backup::where('user_id', $user->id)
                    ->whereNotNull('size_bytes')
                    ->sum('size_bytes');
            }

            return $total;
        });
    }

    public function quotaBytes(User $user): int
    {
        return $user->storage_quota_mb * 1048576;
    }

    public function usedPercent(User $user): float
    {
        $quota = $this->quotaBytes($user);

        return $quota > 0 ? round(($this->usedBytes($user) / $quota) * 100, 1) : 0.0;
    }

    public function remainingBytes(User $user): int
    {
        return max(0, $this->quotaBytes($user) - $this->usedBytes($user));
    }

    public function hasHeadroomFor(User $user, int $additionalBytes): bool
    {
        return $this->remainingBytes($user) >= $additionalBytes;
    }

    public function isOverQuota(User $user): bool
    {
        return $this->usedBytes($user) >= $this->quotaBytes($user);
    }

    /**
     * The highest configured warning threshold this account's usage has
     * crossed (80/90/95/100 by default), or null if under all of them.
     * Used to decide whether to fire StorageQuotaThresholdReached.
     */
    public function highestCrossedThreshold(User $user): ?int
    {
        $percent = $this->usedPercent($user);
        $thresholds = config('portway.storage_warning_thresholds');
        rsort($thresholds);

        foreach ($thresholds as $threshold) {
            if ($percent >= $threshold) {
                return $threshold;
            }
        }

        return null;
    }

    public function forget(User $user): void
    {
        Cache::forget("portway:storage_used:{$user->id}");
    }

    public function usedHuman(User $user): string
    {
        return $this->humanBytes($this->usedBytes($user));
    }

    public function quotaHuman(User $user): string
    {
        return $this->humanBytes($this->quotaBytes($user));
    }

    private function humanBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }

        return number_format($bytes / 1048576, 1).' MB';
    }
}
