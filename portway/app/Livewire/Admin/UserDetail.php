<?php

namespace App\Livewire\Admin;

use App\Models\ActivityLog;
use App\Models\BlockedIp;
use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
class UserDetail extends Component
{
    public User $user;

    // Quotas form
    public int $storageQuotaMb = 0;

    public int $bandwidthQuotaMb = 0;

    public int $maxWebsites = 0;

    public int $maxDatabases = 0;

    public int $maxDomains = 0;

    public int $maxCronJobs = 0;

    public int $maxBackups = 0;

    public int $maxEmailAccounts = 0;

    public string $selectedRole = '';

    public bool $confirmingSuspend = false;

    public string $suspensionReason = '';

    public bool $confirmingDelete = false;

    // Platform-wide IP block (staff only)
    public string $blockIp = '';

    public string $blockReason = '';

    public function mount(User $user): void
    {
        $this->authorize('view', $user);
        $this->user = $user;

        $this->storageQuotaMb = $user->storage_quota_mb;
        $this->bandwidthQuotaMb = $user->bandwidth_quota_mb;
        $this->maxWebsites = $user->max_websites;
        $this->maxDatabases = $user->max_databases;
        $this->maxDomains = $user->max_domains;
        $this->maxCronJobs = $user->max_cron_jobs;
        $this->maxBackups = $user->max_backups;
        $this->maxEmailAccounts = $user->max_email_accounts;
        $this->selectedRole = $user->roles->first()?->name ?? 'User';
    }

    public function saveQuotas(): void
    {
        $this->authorize('update', $this->user);

        $this->validate([
            'storageQuotaMb' => ['required', 'integer', 'min:0'],
            'bandwidthQuotaMb' => ['required', 'integer', 'min:0'],
            'maxWebsites' => ['required', 'integer', 'min:0'],
            'maxDatabases' => ['required', 'integer', 'min:0'],
            'maxDomains' => ['required', 'integer', 'min:0'],
            'maxCronJobs' => ['required', 'integer', 'min:0'],
            'maxBackups' => ['required', 'integer', 'min:0'],
            'maxEmailAccounts' => ['required', 'integer', 'min:0'],
        ]);

        $this->user->update([
            'storage_quota_mb' => $this->storageQuotaMb,
            'bandwidth_quota_mb' => $this->bandwidthQuotaMb,
            'max_websites' => $this->maxWebsites,
            'max_databases' => $this->maxDatabases,
            'max_domains' => $this->maxDomains,
            'max_cron_jobs' => $this->maxCronJobs,
            'max_backups' => $this->maxBackups,
            'max_email_accounts' => $this->maxEmailAccounts,
        ]);

        $this->dispatch('toast', message: 'Quotas updated.', level: 'success');
    }

    public function changeRole(): void
    {
        $this->authorize('changeRole', $this->user);

        if (! Role::where('name', $this->selectedRole)->exists()) {
            return;
        }

        $this->user->syncRoles([$this->selectedRole]);
        $this->dispatch('toast', message: "Role changed to {$this->selectedRole}.", level: 'success');
    }

    public function confirmSuspend(): void
    {
        $this->confirmingSuspend = true;
    }

    public function suspend(): void
    {
        $this->authorize('suspend', $this->user);

        $this->user->forceFill([
            'is_suspended' => true,
            'suspension_reason' => $this->suspensionReason ?: null,
            'suspended_at' => now(),
            'suspended_by' => Auth::id(),
        ])->save();

        $this->confirmingSuspend = false;
        $this->dispatch('toast', message: 'Account suspended.', level: 'success');
    }

    public function unsuspend(): void
    {
        $this->authorize('suspend', $this->user);

        $this->user->forceFill([
            'is_suspended' => false,
            'suspension_reason' => null,
            'suspended_at' => null,
            'suspended_by' => null,
        ])->save();

        $this->dispatch('toast', message: 'Account reinstated.', level: 'success');
    }

    public function confirmDelete(): void
    {
        $this->confirmingDelete = true;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->user);

        $this->user->delete();
        $this->redirectRoute('admin.users.index', navigate: true);
    }

    public function addBlockedIp(): void
    {
        $this->authorize('update', $this->user);

        $this->validate([
            'blockIp' => ['required', 'ip'],
            'blockReason' => ['nullable', 'string', 'max:255'],
        ]);

        BlockedIp::create([
            'user_id' => $this->user->id,
            'ip_address' => $this->blockIp,
            'reason' => $this->blockReason ?: null,
            'scope' => 'platform',
            'created_by' => Auth::id(),
        ]);

        $this->reset(['blockIp', 'blockReason']);
        $this->dispatch('toast', message: 'IP address blocked platform-wide for this account.', level: 'success');
    }

    public function removeBlockedIp(int $blockedIpId): void
    {
        $this->authorize('update', $this->user);
        BlockedIp::where('user_id', $this->user->id)->where('id', $blockedIpId)->delete();
        $this->dispatch('toast', message: 'IP block removed.', level: 'success');
    }

    public function render()
    {
        return view('livewire.admin.user-detail', [
            'roles' => Role::orderBy('name')->pluck('name'),
            'sites' => $this->user->sites()->latest()->get(),
            'domains' => $this->user->domains()->with('site')->latest()->get(),
            'activity' => ActivityLog::where('user_id', $this->user->id)->latest('id')->limit(20)->get(),
            'loginAttempts' => Auth::user()->can('security.view')
                ? LoginAttempt::where('user_id', $this->user->id)->latest()->limit(15)->get()
                : collect(),
            'blockedIps' => BlockedIp::where('user_id', $this->user->id)->where('scope', 'platform')->latest()->get(),
        ])->title("{$this->user->name} · Admin · Portway");
    }
}
