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

        $this->editingPath = $path;
        $this->editingContent = $this->service()->read($path);
    }

    public function saveFile(): void
    {
        try {
            $this->service()->write($this->editingPath, $this->editingContent);
            $this->dispatch('toast', message: 'File saved.', level: 'success');
        } catch (\Throwable $e) {
            $this->dispatch('toast', message: $e->getMessage(), level: 'danger');
        }
    }

    public function closeEditor(): void
    {
        $this->editingPath = null;
        $this->editingContent = '';
    }

    public function createFolder(): void
    {
        $this->validate(['newName' => ['required', 'string', 'max:255']]);
        $this->service()->createDirectory(trim($this->path.'/'.$this->newName, '/'));
        $this->showCreateFolder = false;
        $this->newName = '';
        $this->dispatch('toast', message: 'Folder created.', level: 'success');
    }

    public function createFile(): void
    {
        $this->validate(['newName' => ['required', 'string', 'max:255']]);
        $this->service()->createFile(trim($this->path.'/'.$this->newName, '/'));
        $this->showCreateFile = false;
        $this->newName = '';
        $this->dispatch('toast', message: 'File created.', level: 'success');
    }

    public function openRename(string $path): void
    {
        $this->renameTarget = $path;
        $this->newName = basename($path);
        $this->showRename = true;
    }

    public function rename(): void
    {
        $this->validate(['newName' => ['required', 'string', 'max:255']]);
        $this->service()->rename($this->renameTarget, $this->newName);
        $this->showRename = false;
        $this->dispatch('toast', message: 'Renamed.', level: 'success');
    }

    public function openPermissions(string $path): void
    {
        $this->permissionsTarget = $path;
        $this->showPermissions = true;
    }

    public function savePermissions(): void
    {
        try {
            $this->service()->setPermissions($this->permissionsTarget, $this->permissionsValue);
            $this->showPermissions = false;
            $this->dispatch('toast', message: 'Permissions updated.', level: 'success');
        } catch (\Throwable $e) {
            $this->dispatch('toast', message: $e->getMessage(), level: 'danger');
        }
    }

    public function delete(string $path): void
    {
        $this->service()->delete($path);
        $this->selected = array_values(array_diff($this->selected, [$path]));
        $this->dispatch('toast', message: 'Deleted.', level: 'success');
    }

    public function deleteSelected(): void
    {
        foreach ($this->selected as $path) {
            $this->service()->delete($path);
        }
        $this->selected = [];
        $this->dispatch('toast', message: 'Deleted selected items.', level: 'success');
    }

    public function zipSelected(): void
    {
        if (empty($this->selected)) {
            return;
        }

        $zipName = trim($this->path.'/archive-'.now()->format('Ymd-His').'.zip', '/');

        try {
            $this->service()->zip($this->selected, $zipName);
            $this->selected = [];
            $this->dispatch('toast', message: 'Archive created.', level: 'success');
        } catch (\Throwable $e) {
            $this->dispatch('toast', message: $e->getMessage(), level: 'danger');
        }
    }

    public function extract(string $path): void
    {
        try {
            $this->service()->extract($path, $this->path);
            $this->dispatch('toast', message: 'Archive extracted.', level: 'success');
        } catch (\Throwable $e) {
            $this->dispatch('toast', message: $e->getMessage(), level: 'danger');
        }
    }

    public function upload(): void
    {
        $this->validate(['uploads.*' => ['file', 'max:512000']]); // 500MB per file ceiling

        foreach ($this->uploads as $file) {
            try {
                $this->service()->upload($this->path, $file);
            } catch (\Throwable $e) {
                $this->dispatch('toast', message: "Upload failed for {$file->getClientOriginalName()}: {$e->getMessage()}", level: 'danger');
            }
        }

        $this->uploads = [];
        $this->dispatch('toast', message: 'Upload complete.', level: 'success');
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
