<?php

namespace App\Livewire\Admin;

use App\Enums\ReleasePlatform;
use App\Enums\ReleaseStatus;
use App\Models\Release;
use App\Services\Releases\ReleaseService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

/**
 * Where owner-deleted builds land. Staff can hand one back to its owner
 * (restore) or destroy it for good (purge) — nothing else in the product
 * can delete a build's bytes.
 */
#[Layout('layouts.app')]
#[Title('Release archive')]
class ReleaseArchive extends Component
{
    use WithPagination;

    public string $search = '';

    public string $platform = '';

    public ?int $restoreTargetId = null;

    public ?int $purgeTargetId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Release::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPlatform(): void
    {
        $this->resetPage();
    }

    public function confirmRestore(int $releaseId): void
    {
        $this->restoreTargetId = $releaseId;
    }

    public function restore(ReleaseService $releases): void
    {
        $release = $this->archivedRelease($this->restoreTargetId);
        $this->authorize('restore', $release);

        try {
            $releases->restoreFromAdminArchive($release, auth()->user());
        } catch (RuntimeException $e) {
            $this->restoreTargetId = null;
            $this->dispatch('toast', message: $e->getMessage(), level: 'danger');

            return;
        }

        $this->restoreTargetId = null;
        $this->dispatch('toast', message: 'Build restored to its owner as an archived build.', level: 'success');
    }

    public function confirmPurge(int $releaseId): void
    {
        $this->purgeTargetId = $releaseId;
    }

    public function purge(ReleaseService $releases): void
    {
        $release = $this->archivedRelease($this->purgeTargetId);
        $this->authorize('forceDelete', $release);

        try {
            $releases->purge($release, auth()->user());
        } catch (RuntimeException $e) {
            $this->purgeTargetId = null;
            $this->dispatch('toast', message: $e->getMessage(), level: 'danger');

            return;
        }

        $this->purgeTargetId = null;
        $this->dispatch('toast', message: 'Build permanently deleted.', level: 'success');
    }

    private function archivedRelease(?int $releaseId): Release
    {
        abort_if($releaseId === null, 404);

        return Release::query()
            ->where('status', ReleaseStatus::Trashed)
            ->findOrFail($releaseId);
    }

    public function render()
    {
        $releases = Release::query()
            ->with(['application', 'user', 'trashedBy'])
            ->where('status', ReleaseStatus::Trashed)
            ->when($this->platform !== '', fn ($query) => $query->where('platform', $this->platform))
            ->when($this->search !== '', function ($query) {
                $term = '%'.$this->search.'%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('version', 'like', $term)
                        ->orWhereHas('application', fn ($app) => $app->where('name', 'like', $term))
                        ->orWhereHas('user', fn ($user) => $user->where('email', 'like', $term));
                });
            })
            ->orderByDesc('trashed_at')
            ->paginate(20);

        return view('livewire.admin.release-archive', [
            'releases' => $releases,
            'platforms' => ReleasePlatform::cases(),
        ]);
    }
}
