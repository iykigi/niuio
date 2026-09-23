<?php

namespace App\Models;

use App\Enums\ReleasePlatform;
use App\Enums\ReleaseStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id', 'name', 'slug', 'tagline', 'description',
        'website_url', 'support_email', 'icon_path', 'is_listed',
    ];

    protected function casts(): array
    {
        return [
            'is_listed' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // --- Relationships ----------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function releases(): HasMany
    {
        return $this->hasMany(Release::class);
    }

    /** Every release except the ones the owner has deleted. */
    public function ownerVisibleReleases(): HasMany
    {
        return $this->releases()->where('status', '!=', ReleaseStatus::Trashed);
    }

    public function activeReleases(): HasMany
    {
        return $this->releases()->where('status', ReleaseStatus::Active);
    }

    // --- Scopes -----------------------------------------------------------

    /**
     * Applications the public download directory may show: listed, and with
     * at least one live build. An application whose only builds are drafts,
     * archived or deleted simply does not exist as far as visitors go.
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->where('is_listed', true)
            ->whereHas('releases', fn (Builder $q) => $q->where('status', ReleaseStatus::Active));
    }

    // --- Helpers ----------------------------------------------------------

    /**
     * The live build for one platform, or null when this application has not
     * published for that platform — the caller uses null to hide the
     * platform entirely rather than showing a dead download button.
     */
    public function activeReleaseFor(ReleasePlatform $platform): ?Release
    {
        return $this->releases()
            ->where('platform', $platform)
            ->where('status', ReleaseStatus::Active)
            ->first();
    }

    /**
     * Live builds keyed by platform value, in platform declaration order.
     * Platforms with nothing published are absent from the result.
     *
     * @return Collection<string, Release>
     */
    public function liveDownloads(): Collection
    {
        $active = $this->releases()
            ->where('status', ReleaseStatus::Active)
            ->get()
            ->keyBy(fn (Release $release) => $release->platform->value);

        return collect(ReleasePlatform::cases())
            ->map(fn (ReleasePlatform $platform) => $active->get($platform->value))
            ->filter()
            ->keyBy(fn (Release $release) => $release->platform->value);
    }

    public function hasAnyLiveRelease(): bool
    {
        return $this->releases()->where('status', ReleaseStatus::Active)->exists();
    }

    public function isVisibleToPublic(): bool
    {
        return $this->is_listed && $this->hasAnyLiveRelease();
    }
}
