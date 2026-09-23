<?php

namespace App\Livewire\Usage;

use App\Models\ResourceUsage;
use App\Services\Storage\StorageUsageCalculator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class UsageOverview extends Component
{
    public function render(StorageUsageCalculator $storage)
    {
        $user = Auth::user();

        $bandwidthQuotaBytes = $user->bandwidth_quota_mb * 1048576;
        $monthToDateBandwidth = (int) ResourceUsage::where('user_id', $user->id)
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('bandwidth_bytes');

        $dailyUsage = ResourceUsage::where('user_id', $user->id)
            ->where('date', '>=', now()->subDays(13)->startOfDay())
            ->selectRaw('date, SUM(bandwidth_bytes) as bandwidth, SUM(requests) as requests')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy(fn ($row) => $row->date->format('Y-m-d'));

        $chart = collect(range(13, 0))->map(function ($daysAgo) use ($dailyUsage) {
            $date = now()->subDays($daysAgo);
            $key = $date->format('Y-m-d');
            $row = $dailyUsage->get($key);

            return [
                'label' => $date->format('M j'),
                'bandwidth' => $row ? (int) $row->bandwidth : 0,
                'requests' => $row ? (int) $row->requests : 0,
            ];
        });

        $maxBandwidth = max(1, $chart->max('bandwidth'));

        $sitesByUsage = $user->sites()->orderByDesc('disk_usage_bytes')->get();

        $categories = config('portway.quota_categories');

        return view('livewire.usage.usage-overview', [
            'storageUsedBytes' => $storage->usedBytes($user),
            'storageQuotaBytes' => $storage->quotaBytes($user),
            'storageUsedPercent' => $storage->usedPercent($user),
            'storageUsedHuman' => $storage->usedHuman($user),
            'storageQuotaHuman' => $storage->quotaHuman($user),
            'websitesBytes' => ($categories['websites'] ?? true) ? (int) $user->sites()->sum('disk_usage_bytes') : 0,
            'databasesBytes' => ($categories['databases'] ?? true) ? (int) $user->databases()->sum('size_bytes') : 0,
            'backupsBytes' => ($categories['backups'] ?? false) ? (int) $user->backups()->whereNotNull('size_bytes')->sum('size_bytes') : 0,
            'bandwidthUsedBytes' => $monthToDateBandwidth,
            'bandwidthQuotaBytes' => $bandwidthQuotaBytes,
            'bandwidthUsedPercent' => $bandwidthQuotaBytes > 0 ? min(100, round(($monthToDateBandwidth / $bandwidthQuotaBytes) * 100, 1)) : 0,
            'chart' => $chart,
            'maxBandwidth' => $maxBandwidth,
            'sitesByUsage' => $sitesByUsage,
            'websitesUsed' => $user->sites()->count(),
            'databasesUsed' => $user->databases()->count(),
            'domainsUsed' => $user->domains()->count(),
        ])->title('Usage · Portway');
    }
}
