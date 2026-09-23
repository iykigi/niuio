<?php

namespace App\Livewire\Sites;

use App\Models\Site;
use App\Services\Files\FileManagerService;
use App\Services\Storage\StorageUsageCalculator;
use Livewire\Attributes\Locked;
use Livewire\Component;

class LogsPanel extends Component
{
    #[Locked]
    public Site $site;

    public ?string $activeFile = null;

    public int $lines = 200;

    public function mount(Site $site): void
    {
        $this->authorize('view', $site);
        $this->site = $site;
    }

    private function service(): FileManagerService
    {
        return new FileManagerService($this->site, app(StorageUsageCalculator::class));
    }

    public function open(string $path): void
    {
        $this->activeFile = $path;
    }

    public function refresh(): void
    {
        // No-op action purely to give the "Refresh" button a wire:click
        // target — render() re-reads the files from disk on every call.
    }

    public function render()
    {
        $files = [];

        try {
            foreach ($this->service()->listDirectory('logs') as $entry) {
                if ($entry['type'] === 'file') {
                    $files[] = $entry;
                }
            }
        } catch (\Throwable) {
            // The logs directory has not been created yet (e.g. a brand
            // new website that has never run a process or deployment).
        }

        usort($files, fn ($a, $b) => ($b['modified_at'] ?? 0) <=> ($a['modified_at'] ?? 0));

        if (! $this->activeFile && ! empty($files)) {
            $this->activeFile = $files[0]['path'];
        }

        $tail = null;

        if ($this->activeFile) {
            try {
                $content = $this->service()->read($this->activeFile);
                $allLines = preg_split('/\r\n|\r|\n/', $content);
                $tail = implode("\n", array_slice($allLines, -$this->lines));
            } catch (\Throwable $e) {
                $tail = null;
            }
        }

        return view('livewire.sites.logs-panel', [
            'files' => $files,
            'tail' => $tail,
        ]);
    }
}
