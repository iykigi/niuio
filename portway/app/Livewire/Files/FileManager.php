<?php

namespace App\Livewire\Files;

use App\Models\Site;
use App\Services\Files\FileManagerService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class FileManager extends Component
{
    use WithFileUploads;

    #[Locked]
    public Site $site;

    public string $path = '';

    public string $search = '';

    public string $sortBy = 'name';

    public string $sortDir = 'asc';

    public array $selected = [];

    // Editor state
    public ?string $editingPath = null;

    public string $editingContent = '';

    // Modals
    public bool $showCreateFolder = false;

    public bool $showCreateFile = false;

    public bool $showRename = false;

    public bool $showPermissions = false;

    public string $newName = '';

    public string $renameTarget = '';

    public string $permissionsTarget = '';

    public string $permissionsValue = '0644';

    public $uploads = [];

    public function mount(Site $site): void
    {
        $this->authorize('view', $site);
        $this->site = $site;
    }

    /**
     * Runs a file operation that changes the site: re-checks write access
     * (mount() only checked read access) and turns the service's
     * validation errors — path traversal, quota, bad names — into a toast
     * instead of a 500 page.
     */
    private function mutate(callable $operation, string $successMessage): bool
    {
        $this->authorize('update', $this->site);

        try {
            $operation($this->service());
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('toast', message: $e->getMessage(), level: 'danger');

            return false;
        }

        $this->dispatch('toast', message: $successMessage, level: 'success');

        return true;
    }

    private function service(): FileManagerService
    {
        return new FileManagerService($this->site, app(\App\Services\Storage\StorageUsageCalculator::class));
    }

    public function openDirectory(string $path): void
    {
        $this->path = $path;
        $this->selected = [];
    }

    public function goUp(): void
    {
        $this->path = trim(dirname($this->path), '.') === '' ? '' : dirname($this->path);
    }

    public function toggleSelect(string $path): void
    {
        if (in_array($path, $this->selected, true)) {
            $this->selected = array_values(array_diff($this->selected, [$path]));
        } else {
            $this->selected[] = $path;
        }
    }

    public function openFile(string $path): void
    {
        if (! $this->service()->isEditable(basename($path))) {
            $this->dispatch('toast', message: 'This file type cannot be edited in the browser.', level: 'warning');

            return;
        }

        try {
            $this->editingContent = $this->service()->read($path);
            $this->editingPath = $path;
        } catch (\Throwable $e) {
            $this->dispatch('toast', message: $e->getMessage(), level: 'danger');
        }
    }

    public function saveFile(): void
    {
        $this->mutate(fn ($files) => $files->write($this->editingPath, $this->editingContent), 'File saved.');
    }

    public function closeEditor(): void
    {
        $this->editingPath = null;
        $this->editingContent = '';
    }

    public function createFolder(): void
    {
        $this->validate(['newName' => ['required', 'string', 'max:255', 'not_regex:#[\\\\/]#']], ['newName.not_regex' => 'Names cannot contain / or \\.']);

        if ($this->mutate(fn ($files) => $files->createDirectory(trim($this->path.'/'.$this->newName, '/')), 'Folder created.')) {
            $this->showCreateFolder = false;
            $this->newName = '';
        }
    }

    public function createFile(): void
    {
        $this->validate(['newName' => ['required', 'string', 'max:255', 'not_regex:#[\\\\/]#']], ['newName.not_regex' => 'Names cannot contain / or \\.']);

        if ($this->mutate(fn ($files) => $files->createFile(trim($this->path.'/'.$this->newName, '/')), 'File created.')) {
            $this->showCreateFile = false;
            $this->newName = '';
        }
    }

    public function openRename(string $path): void
    {
        $this->renameTarget = $path;
        $this->newName = basename($path);
        $this->showRename = true;
    }

    public function rename(): void
    {
        $this->validate(['newName' => ['required', 'string', 'max:255', 'not_regex:#[\\\\/]#']], ['newName.not_regex' => 'Names cannot contain / or \\.']);

        if ($this->mutate(fn ($files) => $files->rename($this->renameTarget, $this->newName), 'Renamed.')) {
            $this->showRename = false;
        }
    }

    public function openPermissions(string $path): void
    {
        $this->permissionsTarget = $path;
        $this->showPermissions = true;
    }

    public function savePermissions(): void
    {
        if ($this->mutate(fn ($files) => $files->setPermissions($this->permissionsTarget, $this->permissionsValue), 'Permissions updated.')) {
            $this->showPermissions = false;
        }
    }

    public function delete(string $path): void
    {
        if ($this->mutate(fn ($files) => $files->delete($path), 'Deleted.')) {
            $this->selected = array_values(array_diff($this->selected, [$path]));
        }
    }

    public function deleteSelected(): void
    {
        $selected = $this->selected;

        if ($this->mutate(function ($files) use ($selected) {
            foreach ($selected as $path) {
                $files->delete($path);
            }
        }, 'Deleted selected items.')) {
            $this->selected = [];
        }
    }

    public function zipSelected(): void
    {
        if (empty($this->selected)) {
            return;
        }

        $zipName = trim($this->path.'/archive-'.now()->format('Ymd-His').'.zip', '/');

        if ($this->mutate(fn ($files) => $files->zip($this->selected, $zipName), 'Archive created.')) {
            $this->selected = [];
        }
    }

    public function extract(string $path): void
    {
        $this->mutate(fn ($files) => $files->extract($path, $this->path), 'Archive extracted.');
    }

    /**
     * Livewire calls this once the selected files have finished uploading
     * to its temporary storage.
     */
    public function updatedUploads(): void
    {
        $this->upload();
    }

    public function upload(): void
    {
        $this->authorize('update', $this->site);
        $this->validate(['uploads.*' => ['file', 'max:512000']]); // 500MB per file ceiling

        $failed = 0;

        foreach ($this->uploads as $file) {
            try {
                $this->service()->upload($this->path, $file);
            } catch (\Throwable $e) {
                $failed++;
                $this->dispatch('toast', message: "Upload failed for {$file->getClientOriginalName()}: {$e->getMessage()}", level: 'danger');
            }
        }

        $uploaded = count($this->uploads) - $failed;
        $this->uploads = [];

        if ($uploaded > 0) {
            $this->dispatch('toast', message: $uploaded === 1 ? 'File uploaded.' : "{$uploaded} files uploaded.", level: 'success');
        }
    }

    public function downloadUrl(string $path): string
    {
        return route('files.download', ['site' => $this->site->id, 'path' => $path]);
    }

    public function breadcrumbs(): array
    {
        if ($this->path === '') {
            return [];
        }

        $parts = explode('/', $this->path);
        $crumbs = [];
        $accumulated = '';

        foreach ($parts as $part) {
            $accumulated = trim($accumulated.'/'.$part, '/');
            $crumbs[] = ['label' => $part, 'path' => $accumulated];
        }

        return $crumbs;
    }

    public function render()
    {
        $entries = $this->service()->listDirectory($this->path, $this->search, $this->sortBy, $this->sortDir);

        return view('livewire.files.file-manager', [
            'entries' => $entries,
            'breadcrumbs' => $this->breadcrumbs(),
        ]);
    }
}
