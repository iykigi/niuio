<?php

namespace App\Models;

use App\Enums\ReleasePlatform;
use App\Enums\ReleaseStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Release extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id', 'user_id', 'platform', 'version', 'status',
        'disk', 'path', 'original_filename', 'size_bytes', 'checksum_sha256',
        'architecture', 'minimum_os', 'changelog',
        'published_at',
        'archived_at',
        'trashed_at',
        'trashed_by',
    ];

    protected function casts(): array
    {
        return [
            'platform' => ReleasePlatform::class,
            'status' => ReleaseStatus::class,
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
            'trashed_at' => 'datetime',
        ];
    }

    // --- Relationships ----------------------------------------------------

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trashedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trashed_by');
    }

    // --- Scopes -----------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ReleaseStatus::Active);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', ReleaseStatus::Archived);
    }

    public function scopeTrashed(Builder $query): Builder
    {
        return $query->where('status', ReleaseStatus::Trashed);
    }

    /** Everything the owner should still see in their own panel. */
    public function scopeNotTrashed(Builder $query): Builder
    {
        return $query->where('status', '!=', ReleaseStatus::Trashed);
    }

    public function scopeForPlatform(Builder $query, ReleasePlatform $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    // --- State helpers ----------------------------------------------------

    public function isActive(): bool
    {
        return $this->status === ReleaseStatus::Active;
    }

    public function isDraft(): bool
    {
        return $this->status === ReleaseStatus::Draft;
    }

    public function isArchived(): bool
    {
        return $this->status === ReleaseStatus::Archived;
    }

    public function isTrashed(): bool
    {
        return $this->status === ReleaseStatus::Trashed;
    }

    /**
     * A build may only go live once it actually has a file and the metadata
     * a visitor needs to make sense of it. This is what enforces the
     * "upload the build with all of its information first, then publish"
     * rule — publishing is never the same step as uploading.
     */
    public function isReadyToPublish(): bool
    {
        return $this->missingPublishRequirements() === [];
    }

    /**
     * @return array<int, string> human-readable list of what is still missing
     */
    public function missingPublishRequirements(): array
    {
        $missing = [];

        if (blank($this->path) || blank($this->original_filename)) {
            $missing[] = 'the build file';
        }

        if (blank($this->version)) {
            $missing[] = 'a version number';
        }

        if (blank($this->changelog)) {
            $missing[] = 'release notes';
        }

        if (blank($this->checksum_sha256)) {
            $missing[] = 'a checksum (recomputed automatically on upload)';
        }

        return $missing;
    }

    public function sizeHuman(): string
    {
        if (! $this->size_bytes) {
            return '—';
        }

        $mb = $this->size_bytes / 1048576;

        return $mb >= 1024
            ? number_format($mb / 1024, 2).' GB'
            : number_format($mb, 1).' MB';
    }

    public function shortChecksum(): string
    {
        return $this->checksum_sha256 ? substr($this->checksum_sha256, 0, 12).'…' : '—';
    }
}
