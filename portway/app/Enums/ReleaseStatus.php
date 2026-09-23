<?php

namespace App\Enums;

enum ReleaseStatus: string
{
    /** Uploaded, metadata possibly incomplete, never visible to the public. */
    case Draft = 'draft';

    /** The one live build for its platform — the only status the public sees. */
    case Active = 'active';

    /** Superseded or manually retired. Visible to its owner and to staff only. */
    case Archived = 'archived';

    /** Deleted by its owner. Visible to staff only, in the admin archive. */
    case Trashed = 'trashed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Live',
            self::Archived => 'Archived',
            self::Trashed => 'Deleted',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'badge-success',
            self::Draft => 'badge-info',
            self::Archived => 'badge-neutral',
            self::Trashed => 'badge-danger',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Draft => 'Uploaded but not published yet. Nobody outside your account can see or download it.',
            self::Active => 'The build the public download page serves for this platform.',
            self::Archived => 'A previous build, kept for your records. Hidden from the public page.',
            self::Trashed => 'Deleted by the owner. Retained in the admin archive until an administrator restores or purges it.',
        };
    }
}
