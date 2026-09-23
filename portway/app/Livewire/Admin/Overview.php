<?php

namespace App\Livewire\Admin;

use App\Enums\BackupStatus;
use App\Models\ActivityLog;
use App\Models\Backup;
use App\Models\Database;
use App\Models\Domain;
use App\Models\Server;
use App\Models\Site;
use App\Models\SupportTicket;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Overview extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function render()
    {
        return view('livewire.admin.overview', [
            'totalUsers' => User::count(),
            'suspendedUsers' => User::where('is_suspended', true)->count(),
            'newUsersThisWeek' => User::where('created_at', '>=', now()->subDays(7))->count(),
            'totalSites' => Site::count(),
            'activeSites' => Site::where('status', 'active')->count(),
            'totalDomains' => Domain::count(),
            'totalDatabases' => Database::count(),
            'totalBackups' => Backup::where('status', BackupStatus::Completed)->count(),
            'onlineServers' => Server::where('status', 'online')->count(),
            'totalServers' => Server::count(),
            'openTickets' => SupportTicket::whereIn('status', ['open', 'pending'])->count(),
            'unassignedTickets' => SupportTicket::whereIn('status', ['open', 'pending'])->whereNull('assigned_to')->count(),
            'totalStorageBytes' => (int) Site::sum('disk_usage_bytes') + (int) Database::sum('size_bytes'),
            'recentUsers' => User::latest()->limit(6)->get(),
            'recentActivity' => ActivityLog::with('user')->latest('id')->limit(12)->get(),
        ])->title('Admin · Portway');
    }
}
