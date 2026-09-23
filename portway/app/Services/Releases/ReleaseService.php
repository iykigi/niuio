<?php

namespace App\Services\Releases;

use App\Enums\ReleasePlatform;
use App\Enums\ReleaseStatus;
use App\Exceptions\QuotaExceededException;
use App\Models\Application;
use App\Models\Release;
use App\Models\User;
use App\Services\Security\ActivityLogger;
use App\Services\Settings;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Owns every state transition a downloadable build can go through.
 *
 * The rules this class exists to guarantee:
 *
 *  - Exactly one build per platform is ever live at a time. Publishing a
 *    new one automatically archives whatever it replaces, inside one
 *    transaction, so the public page can never see two or zero-by-accident.
 *  - A platform with no live build is simply absent from the public page —
 *    there is no such thing as a broken or "coming soon" download link.
 *  - Uploading and publishing are deliberately separate steps: a build is
 *    uploaded with its complete metadata first, and only goes live on an
 *    explicit publish.
 *  - Deleting is never destructive for the owner. An owner-deleted build
 *    moves into the admin archive, where staff can restore it to the owner
 *    or purge it for good.
 */
class ReleaseService
{
    public function __construct(
        private readonly ActivityLogger $logger,
        private readonly Settings $settings,
    ) {}

    // --- Creation ---------------------------------------------------------

    /**
     * Store an uploaded build as a draft. Nothing is visible to the public
     * as a result of this call — publish() is a separate, explicit step.
     *
     * @param  array{version: string, changelog: ?string, architecture: ?string, minimum_os: ?string}  $metadata
     */
    public function createDraft(
        User $owner,
        Application $application,
        ReleasePlatform $platform,
        UploadedFile $file,
        array $metadata,
    ): Release {
        $this->assertWithinReleaseQuota($application);
        $this->assertUploadIsAcceptable($platform, $file);

        $version = trim($metadata['version']);

        $duplicate = $application->releases()
            ->where('platform', $platform)
            ->where('version', $version)
            ->exists();

        if ($duplicate) {
            throw new RuntimeException("Version {$version} already exists for {$platform->label()}. Use a different version number.");
        }

        $stored = $this->storeFile($owner, $application, $platform, $file);

        $release = new Release([
            'application_id' => $application->id,
            'user_id' => $owner->id,
            'platform' => $platform,
            'version' => $version,
            'status' => ReleaseStatus::Draft,
            'disk' => $stored['disk'],
            'path' => $stored['path'],
            'original_filename' => $stored['original_filename'],
            'size_bytes' => $stored['size_bytes'],
            'checksum_sha256' => $stored['checksum'],
            'architecture' => $metadata['architecture'] ?? null,
            'minimum_os' => $metadata['minimum_os'] ?? null,
            'changelog' => $metadata['changelog'] ?? null,
        ]);

        $release->save();

        $this->logger->log(
            'release.uploaded',
            $release,
            "Uploaded {$application->name} {$version} for {$platform->label()}",
            $owner->id,
            ['platform' => $platform->value, 'version' => $version],
        );

        return $release;
    }

    // --- Publishing -------------------------------------------------------

    /**
     * Make this build the live one for its platform, archiving whichever
     * build currently holds that slot. Used both for first publication of a
     * draft and for rolling back to an archived build.
     */
    public function publish(Release $release, User $actor): Release
    {
        if ($release->isTrashed()) {
            throw new RuntimeException('A deleted build has to be restored by an administrator before it can go live again.');
        }

        if (! $release->isReadyToPublish()) {
            $missing = implode(', ', $release->missingPublishRequirements());

            throw new RuntimeException("This build is missing {$missing}. Complete it before publishing.");
        }

        if (! $this->fileExists($release)) {
            throw new RuntimeException('The build file for this release is missing from storage. Re-upload it before publishing.');
        }

        DB::transaction(function () use ($release, $actor) {
            // Lock every row in this application/platform slot so two
            // concurrent publishes cannot both believe they won.
            $contenders = Release::query()
                ->where('application_id', $release->application_id)
                ->where('platform', $release->platform)
                ->lockForUpdate()
                ->get();

            foreach ($contenders as $contender) {
                if ($contender->id === $release->id || $contender->status !== ReleaseStatus::Active) {
                    continue;
                }

                $contender->forceFill([
                    'status' => ReleaseStatus::Archived,
                    'archived_at' => now(),
                ])->save();

                $this->logger->log(
                    'release.archived',
                    $contender,
                    "Archived {$contender->version} for {$contender->platform->label()} — superseded by {$release->version}",
                    $actor->id,
                    ['platform' => $contender->platform->value, 'version' => $contender->version, 'reason' => 'superseded'],
                );
            }

            $release->forceFill([
                'status' => ReleaseStatus::Active,
                'published_at' => $release->published_at ?? now(),
                'archived_at' => null,
            ])->save();
        });

        $this->logger->log(
            'release.published',
            $release,
            "Published {$release->application->name} {$release->version} for {$release->platform->label()}",
            $actor->id,
            ['platform' => $release->platform->value, 'version' => $release->version],
        );

        return $release->refresh();
    }

    /**
     * Retire the live build for a platform without putting anything in its
     * place. The platform then disappears from the public page entirely.
     */
    public function archive(Release $release, User $actor): Release
    {
        if (! $release->isActive()) {
            throw new RuntimeException('Only the live build can be taken down.');
        }

        $release->forceFill([
            'status' => ReleaseStatus::Archived,
            'archived_at' => now(),
        ])->save();

        $this->logger->log(
            'release.archived',
            $release,
            "Took {$release->version} for {$release->platform->label()} offline",
            $actor->id,
            ['platform' => $release->platform->value, 'version' => $release->version, 'reason' => 'manual'],
        );

        return $release;
    }

    // --- Deletion / admin archive ----------------------------------------

    /**
     * The owner's delete. Non-destructive by design: the build leaves the
     * owner's panel and lands in the admin archive, file intact.
     */
    public function moveToAdminArchive(Release $release, User $actor): Release
    {
        if ($release->isTrashed()) {
            return $release;
        }

        $release->forceFill([
            'status' => ReleaseStatus::Trashed,
            'trashed_at' => now(),
            'trashed_by' => $actor->id,
            'archived_at' => $release->archived_at ?? now(),
        ])->save();

        $this->logger->log(
            'release.deleted',
            $release,
            "Deleted {$release->version} for {$release->platform->label()} — moved to the admin archive",
            $actor->id,
            ['platform' => $release->platform->value, 'version' => $release->version],
        );

        return $release;
    }

    /** An administrator hands a deleted build back to its owner, as an archived build. */
    public function restoreFromAdminArchive(Release $release, User $actor): Release
    {
        if (! $release->isTrashed()) {
            throw new RuntimeException('That build is not in the archive.');
        }

        $release->forceFill([
            'status' => ReleaseStatus::Archived,
            'trashed_at' => null,
            'trashed_by' => null,
            'archived_at' => now(),
        ])->save();

        $this->logger->log(
            'release.restored',
            $release,
            "Restored {$release->version} for {$release->platform->label()} to its owner",
            $actor->id,
            ['platform' => $release->platform->value, 'version' => $release->version],
        );

        return $release;
    }

    /** An administrator destroys a build for good, file and row together. */
    public function purge(Release $release, User $actor): void
    {
        if (! $release->isTrashed()) {
            throw new RuntimeException('Only builds in the admin archive can be permanently deleted.');
        }

        $description = "Permanently deleted {$release->version} for {$release->platform->label()}";
        $metadata = [
            'platform' => $release->platform->value,
            'version' => $release->version,
            'application_id' => $release->application_id,
            'owner_id' => $release->user_id,
        ];

        $this->deleteFile($release);

        // Log before the row disappears so the activity chain keeps a record
        // of a build the database no longer has.
        $this->logger->log('release.purged', $release, $description, $actor->id, $metadata);

        $release->delete();
    }

    // --- Downloads --------------------------------------------------------

    public function recordDownload(Release $release): void
    {
        DB::transaction(function () use ($release) {
            Release::whereKey($release->id)->increment('downloads_count');
            Application::whereKey($release->application_id)->increment('downloads_count');
        });
    }

    // --- Files ------------------------------------------------------------

    /**
     * @return array{disk: string, path: string, original_filename: string, size_bytes: int, checksum: string}
     */
    private function storeFile(User $owner, Application $application, ReleasePlatform $platform, UploadedFile $file): array
    {
        $disk = 'releases';

        // The stored name is generated, never the visitor-supplied one: the
        // original is kept only as a display label and as the download
        // filename, which is sanitised on the way back out.
        $extension = strtolower($file->getClientOriginalExtension());
        $name = Str::uuid()->toString().($extension !== '' ? '.'.$extension : '');
        $directory = "{$owner->id}/{$application->id}/{$platform->value}";

        $checksum = hash_file('sha256', $file->getRealPath());
        $size = $file->getSize();

        $path = $file->storeAs($directory, $name, ['disk' => $disk]);

        if ($path === false) {
            throw new RuntimeException('The build could not be saved to storage. Try the upload again.');
        }

        return [
            'disk' => $disk,
            'path' => $path,
            'original_filename' => $this->sanitiseFilename($file->getClientOriginalName()),
            'size_bytes' => (int) $size,
            'checksum' => (string) $checksum,
        ];
    }

    public function fileExists(Release $release): bool
    {
        return filled($release->path) && Storage::disk($release->disk)->exists($release->path);
    }

    private function deleteFile(Release $release): void
    {
        if ($this->fileExists($release)) {
            Storage::disk($release->disk)->delete($release->path);
        }
    }

    /**
     * Strip anything that could make the download's Content-Disposition
     * filename behave as a path or header injection.
     */
    public function sanitiseFilename(string $filename): string
    {
        $base = basename(str_replace('\\', '/', $filename));
        $base = preg_replace('/[^A-Za-z0-9._-]+/', '_', $base) ?? 'download';
        $base = ltrim($base, '.');

        return $base === '' ? 'download' : Str::limit($base, 120, '');
    }

    // --- Guards -----------------------------------------------------------

    private function assertUploadIsAcceptable(ReleasePlatform $platform, UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw new RuntimeException('The upload did not complete. Try again.');
        }

        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, $platform->allowedExtensions(), true)) {
            throw new RuntimeException(
                "A {$platform->label()} build has to be one of: {$platform->extensionHint()}."
            );
        }

        $maxMb = $this->maxReleaseSizeMb();

        if ($file->getSize() > $maxMb * 1048576) {
            throw new QuotaExceededException("Builds are limited to {$maxMb} MB. This file is larger.");
        }
    }

    private function assertWithinReleaseQuota(Application $application): void
    {
        $max = (int) $this->settings->get(
            'max_releases_per_application',
            config('portway.releases.max_per_application', 50)
        );

        if ($max > 0 && $application->releases()->count() >= $max) {
            throw new QuotaExceededException(
                "This application already has the maximum of {$max} builds. Ask an administrator to purge old ones from the archive."
            );
        }
    }

    public function maxReleaseSizeMb(): int
    {
        return (int) $this->settings->get(
            'max_release_size_mb',
            config('portway.releases.max_size_mb', 2048)
        );
    }

    /**
     * Resolve a release the given user is allowed to see, or fail. Used by
     * the download controller, which must never leak a draft, archived or
     * deleted build to someone who is not its owner or staff.
     */
    public function findDownloadable(Application $application, ReleasePlatform $platform, ?User $viewer = null): Release
    {
        $release = $application->activeReleaseFor($platform);

        if ($release && $application->is_listed) {
            return $release;
        }

        // Owners and staff may still pull their own unpublished build (for
        // example to check an archived installer) — nobody else can.
        if ($viewer && ($viewer->id === $application->user_id || $viewer->can('releases.manage'))) {
            $own = $application->releases()
                ->where('platform', $platform)
                ->where('status', '!=', ReleaseStatus::Trashed)
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->first();

            if ($own) {
                return $own;
            }
        }

        throw new ModelNotFoundException('No published build for that platform.');
    }
}
