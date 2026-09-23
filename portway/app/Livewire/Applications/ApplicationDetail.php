<?php

namespace App\Livewire\Applications;

use App\Enums\ReleasePlatform;
use App\Enums\ReleaseStatus;
use App\Exceptions\QuotaExceededException;
use App\Models\Application;
use App\Models\Release;
use App\Services\Releases\ReleaseService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

#[Layout('layouts.app')]
class ApplicationDetail extends Component
{
    use WithFileUploads;

    #[Locked]
    public Application $application;

    public string $tab = 'releases';

    // --- Application settings form ---------------------------------------

    public string $name = '';

    public string $tagline = '';

    public string $description = '';

    public string $website_url = '';

    public string $support_email = '';

    public bool $is_listed = true;

    // --- Upload form ------------------------------------------------------

    public bool $showUpload = false;

    public string $uploadPlatform = 'windows';

    public string $version = '';

    public string $architecture = '';

    public string $minimum_os = '';

    public string $changelog = '';

    public $buildFile;

    // --- Confirmation targets --------------------------------------------

    public ?int $publishTargetId = null;

    public ?int $deleteTargetId = null;

    public ?int $takeOfflineTargetId = null;

    public function mount(Application $application, ?string $tab = null): void
    {
        $this->authorize('view', $application);

        $this->application = $application;
        $this->tab = in_array($tab, ['releases', 'archive', 'settings'], true) ? $tab : 'releases';

        $this->name = $application->name;
        $this->tagline = (string) $application->tagline;
        $this->description = (string) $application->description;
        $this->website_url = (string) $application->website_url;
        $this->support_email = (string) $application->support_email;
        $this->is_listed = $application->is_listed;
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['releases', 'archive', 'settings'], true)) {
            $this->tab = $tab;
        }
    }

    // --- Settings ---------------------------------------------------------

    public function saveSettings(): void
    {
        $this->authorize('update', $this->application);

        $data = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'tagline' => ['nullable', 'string', 'max:140'],
            'description' => ['nullable', 'string', 'max:5000'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'is_listed' => ['boolean'],
        ]);

        $this->application->update($data);

        $this->dispatch('toast', message: 'Application details saved.', level: 'success');
    }

    // --- Uploading a build ------------------------------------------------

    public function openUpload(string $platform): void
    {
        // Both checks matter: "may this account create builds at all" and
        // "may it put one into *this* application". Without the second, any
        // staff member who can merely view an application (releases.view)
        // could file a build into someone else's product.
        $this->authorize('create', Release::class);
        $this->authorize('update', $this->application);

        abort_if(ReleasePlatform::tryFrom($platform) === null, 404);

        $this->reset(['version', 'architecture', 'minimum_os', 'changelog', 'buildFile']);
        $this->resetValidation();
        $this->uploadPlatform = $platform;
        $this->showUpload = true;
    }

    /**
     * Uploads the build and files it as a draft. This deliberately does not
     * publish: the build goes live only when the owner presses Publish,
     * which is the separate step below.
     */
    public function upload(ReleaseService $releases): void
    {
        $this->authorize('create', Release::class);
        $this->authorize('update', $this->application);

        $platform = ReleasePlatform::tryFrom($this->uploadPlatform);
        abort_if($platform === null, 404);

        $maxKb = $releases->maxReleaseSizeMb() * 1024;

        $this->validate([
            'version' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9][A-Za-z0-9._+-]*$/'],
            'architecture' => ['nullable', 'string', 'max:40'],
            'minimum_os' => ['nullable', 'string', 'max:80'],
            'changelog' => ['required', 'string', 'min:3', 'max:20000'],
            'buildFile' => ['required', 'file', 'max:'.$maxKb],
        ], [
            'version.regex' => 'Use a plain version number such as 1.4.2 or 2026.3.0-beta.',
            'changelog.required' => 'Describe what changed in this build — visitors see these notes on the download page.',
            'buildFile.required' => 'Choose the installer file for this platform.',
        ]);

        try {
            $releases->createDraft(
                auth()->user(),
                $this->application,
                $platform,
                $this->buildFile,
                [
                    'version' => $this->version,
                    'architecture' => $this->architecture ?: null,
                    'minimum_os' => $this->minimum_os ?: null,
                    'changelog' => $this->changelog,
                ],
            );
        } catch (QuotaExceededException|RuntimeException $e) {
            $this->addError('buildFile', $e->getMessage());

            return;
        }

        $this->reset(['version', 'architecture', 'minimum_os', 'changelog', 'buildFile']);
        $this->showUpload = false;

        $this->dispatch('toast', message: 'Build uploaded as a draft. Publish it when you are ready for it to go live.', level: 'success');
    }

    // --- Publishing / rolling back ---------------------------------------

    public function confirmPublish(int $releaseId): void
    {
        $this->publishTargetId = $releaseId;
    }

    /**
     * Take a draft or archived build live. Whatever currently holds that
     * platform's slot is archived automatically inside the service, so the
     * two are never both visible.
     */
    public function publish(ReleaseService $releases): void
    {
        $release = $this->ownRelease($this->publishTargetId);
        $this->authorize('publish', $release);

        try {
            $releases->publish($release, auth()->user());
        } catch (RuntimeException $e) {
            $this->publishTargetId = null;
            $this->dispatch('toast', message: $e->getMessage(), level: 'danger');

            return;
        }

        $this->publishTargetId = null;
        $this->dispatch('toast', message: "Version {$release->version} is now live for {$release->platform->label()}.", level: 'success');
    }

    public function confirmTakeOffline(int $releaseId): void
    {
        $this->takeOfflineTargetId = $releaseId;
    }

    /** Retire the live build without replacing it: the platform disappears. */
    public function takeOffline(ReleaseService $releases): void
    {
        $release = $this->ownRelease($this->takeOfflineTargetId);
        $this->authorize('publish', $release);

        try {
            $releases->archive($release, auth()->user());
        } catch (RuntimeException $e) {
            $this->takeOfflineTargetId = null;
            $this->dispatch('toast', message: $e->getMessage(), level: 'danger');

            return;
        }

        $this->takeOfflineTargetId = null;
        $this->dispatch('toast', message: "{$release->platform->label()} downloads are now hidden from the public page.", level: 'success');
    }

    // --- Deleting ---------------------------------------------------------

    public function confirmDelete(int $releaseId): void
    {
        $this->deleteTargetId = $releaseId;
    }

    /**
     * The owner's delete. The build leaves this panel but is not destroyed:
     * it moves to the admin archive, where staff can restore or purge it.
     */
    public function delete(ReleaseService $releases): void
    {
        $release = $this->ownRelease($this->deleteTargetId);
        $this->authorize('delete', $release);

        $releases->moveToAdminArchive($release, auth()->user());

        $this->deleteTargetId = null;
        $this->dispatch('toast', message: 'Build deleted. An administrator can still restore it from the archive.', level: 'success');
    }

    // --- Internals --------------------------------------------------------

    /**
     * Always resolve a release through this application, so an id from a
     * tampered request can never reach another account's build.
     */
    private function ownRelease(?int $releaseId): Release
    {
        abort_if($releaseId === null, 404);

        return $this->application->releases()
            ->where('status', '!=', ReleaseStatus::Trashed)
            ->findOrFail($releaseId);
    }

    public function render()
    {
        $releases = $this->application->releases()
            ->where('status', '!=', ReleaseStatus::Trashed)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        return view('livewire.applications.application-detail', [
            'platforms' => ReleasePlatform::cases(),
            'liveByPlatform' => $releases->where('status', ReleaseStatus::Active)->keyBy(fn (Release $r) => $r->platform->value),
            'releasesByPlatform' => $releases->groupBy(fn (Release $r) => $r->platform->value),
            'draftCount' => $releases->where('status', ReleaseStatus::Draft)->count(),
            'archivedReleases' => $releases->where('status', ReleaseStatus::Archived),
            'publicUrl' => $this->application->isVisibleToPublic()
                ? route('apps.show', $this->application->slug)
                : null,
        ]);
    }
}
