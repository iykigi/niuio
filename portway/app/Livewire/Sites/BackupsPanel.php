<?php

namespace App\Livewire\Sites;

use App\Enums\BackupStatus;
use App\Exceptions\QuotaExceededException;
use App\Models\Backup;
use App\Models\Site;
use App\Services\Backups\BackupService;
use Livewire\Attributes\Locked;
use Livewire\Component;

class BackupsPanel extends Component
{
    #[Locked]
    public Site $site;

    public string $type = 'full';

    public ?int $restoreTargetId = null;

    public ?int $deleteTargetId = null;

    public function mount(Site $site): void
    {
        $this->authorize('view', $site);
        $this->site = $site;
    }

    public function create(BackupService $service): void
    {
        $this->authorize('create', Backup::class);

        $this->validate([
            'type' => ['required', 'in:files,database,full'],
        ]);

        try {
            $backup = $service->create(auth()->user(), $this->site, $this->type, 'manual');
            $this->dispatch('toast', message: $backup->fresh()->status === BackupStatus::Completed ? 'Backup created.' : 'Backup queued.', level: 'success');
        } catch (QuotaExceededException $e) {
            $this->dispatch('toast', message: $e->getMessage(), level: 'danger');
        } catch (\Throwable $e) {
            // With the "sync" queue the job runs inside this request; it has
            // already marked the backup failed with the reason.
            report($e);
            $this->dispatch('toast', message: 'Backup failed: '.$e->getMessage(), level: 'danger');
        }
    }

    public function confirmRestore(int $backupId): void
    {
        $this->restoreTargetId = $backupId;
    }

    public function restore(BackupService $service): void
    {
        $backup = Backup::where('site_id', $this->site->id)->findOrFail($this->restoreTargetId);
        $this->authorize('restore', $backup);

        $this->restoreTargetId = null;

        try {
            $service->restore($backup);
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Restore failed: '.$e->getMessage(), level: 'danger');

            return;
        }

        $this->dispatch('toast', message: $backup->fresh()->status === BackupStatus::Restored ? 'Backup restored.' : 'Restore started.', level: 'success');
    }

    public function confirmDelete(int $backupId): void
    {
        $this->deleteTargetId = $backupId;
    }

    public function delete(BackupService $service): void
    {
        $backup = Backup::where('site_id', $this->site->id)->findOrFail($this->deleteTargetId);
        $this->authorize('delete', $backup);

        $service->delete($backup);
        $this->deleteTargetId = null;
        $this->dispatch('toast', message: 'Backup deleted.', level: 'success');
    }

    public function render()
    {
        return view('livewire.sites.backups-panel', [
            'backups' => Backup::where('site_id', $this->site->id)->latest()->get(),
            'statuses' => BackupStatus::class,
            'maxBackups' => auth()->user()->max_backups,
            'usedBackups' => Backup::where('site_id', $this->site->id)->where('status', '!=', BackupStatus::Failed)->count(),
        ]);
    }
}
