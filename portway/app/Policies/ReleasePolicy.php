<?php

namespace App\Policies;

use App\Models\Release;
use App\Models\User;

class ReleasePolicy
{
    /** Only staff browse the release archive as a list. */
    public function viewAny(User $user): bool
    {
        return $user->can('releases.view') || $user->can('releases.manage');
    }

    /**
     * A deleted build lives in the admin archive: its owner can no longer
     * see it at all, only staff can. Every other state is visible to the
     * owner and to staff with the releases.view permission.
     */
    public function view(User $user, Release $release): bool
    {
        if ($release->isTrashed()) {
            return $user->can('releases.manage');
        }

        return $user->id === $release->user_id || $user->can('releases.view');
    }

    public function create(User $user): bool
    {
        return ! $user->is_suspended;
    }

    public function update(User $user, Release $release): bool
    {
        if ($release->isTrashed()) {
            return $user->can('releases.manage');
        }

        if ($user->id === $release->user_id) {
            return ! $user->is_suspended;
        }

        return $user->can('releases.manage');
    }

    /** Taking a build live, or rolling back to an older one. */
    public function publish(User $user, Release $release): bool
    {
        return $this->update($user, $release);
    }

    /** The owner's delete: moves the build into the admin archive. */
    public function delete(User $user, Release $release): bool
    {
        if ($release->isTrashed()) {
            return false;
        }

        return $this->update($user, $release);
    }

    /** Staff-only: hand an archived build back to its owner. */
    public function restore(User $user, Release $release): bool
    {
        return $release->isTrashed() && $user->can('releases.manage');
    }

    /** Staff-only: destroy the build and its file for good. */
    public function forceDelete(User $user, Release $release): bool
    {
        return $release->isTrashed() && $user->can('releases.manage');
    }

    /**
     * A build is downloadable by anyone only when it is genuinely public:
     * live *and* belonging to a listed application. `is_listed` is the
     * owner's kill switch for the whole listing, so an unlisted
     * application's live build must not be reachable by guessing a release
     * id either — only its owner and staff may still pull it.
     */
    public function download(User $user, Release $release): bool
    {
        if ($release->isActive() && $release->application?->is_listed) {
            return true;
        }

        return $this->view($user, $release);
    }
}
