<?php

namespace App\Livewire\Dashboard;

use App\Models\Backup;
use App\Services\Storage\StorageUsageCalculator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Home extends Component
{
    public function render(StorageUsageCalculator $calculator)
    {
        $user = auth()->user();
        $sites = $user->sites()->with('domains')->latest()->get();

        return view('livewire.dashboard.home', [
            'sites' => $sites,
            'activeSitesCount' => $sites->filter(fn ($s) => $s->isActive())->count(),
            'usedBytes' => $calculator->usedBytes($user),
            'usedPercent' => $calculator->usedPercent($user),
            'usedHuman' => $calculator->usedHuman($user),
            'quotaHuman' => $calculator->quotaHuman($user),
            'databaseCount' => $user->databases()->count(),
            'domainCount' => $user->domains()->count(),
            'lastBackup' => Backup::where('user_id', $user->id)->where('status', 'completed')->latest('completed_at')->first(),
            'recentActivity' => $user->activityLogs()->latest('created_at')->limit(8)->get(),
            'sslActiveCount' => \App\Models\SslCertificate::whereHas('domain', fn ($q) => $q->where('user_id', $user->id))
                ->where('status', 'active')->count(),
        ]);
    }
}
