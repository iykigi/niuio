<?php

namespace App\Livewire;

use App\Models\Database;
use App\Models\Domain;
use App\Models\Site;
use Livewire\Attributes\On;
use Livewire\Component;

class CommandPalette extends Component
{
    public bool $open = false;

    public string $query = '';

    #[On('command-palette:toggle')]
    public function toggle(): void
    {
        $this->open = ! $this->open;
        $this->query = '';
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function getResultsProperty(): array
    {
        $user = auth()->user();

        if ($this->query === '') {
            return $this->staticActions();
        }

        $results = [];

        Site::where('user_id', $user->id)->where('name', 'like', "%{$this->query}%")->limit(5)->get()
            ->each(function (Site $site) use (&$results) {
                $results[] = ['label' => $site->name, 'sub' => 'Website', 'url' => route('sites.show', $site), 'icon' => 'globe-alt'];
            });

        Domain::where('user_id', $user->id)->where('hostname', 'like', "%{$this->query}%")->limit(5)->get()
            ->each(function (Domain $domain) use (&$results) {
                $results[] = ['label' => $domain->hostname, 'sub' => 'Domain', 'url' => route('domains.show', $domain), 'icon' => 'link'];
            });

        Database::where('user_id', $user->id)->where('name', 'like', "%{$this->query}%")->limit(5)->get()
            ->each(function (Database $database) use (&$results) {
                $results[] = ['label' => $database->name, 'sub' => 'Database', 'url' => route('databases.show', $database), 'icon' => 'circle-stack'];
            });

        foreach ($this->staticActions() as $action) {
            if (str_contains(strtolower($action['label']), strtolower($this->query))) {
                $results[] = $action;
            }
        }

        return $results;
    }

    private function staticActions(): array
    {
        return [
            ['label' => 'Create website', 'sub' => 'Action', 'url' => route('sites.create'), 'icon' => 'plus-circle'],
            ['label' => 'Add domain', 'sub' => 'Action', 'url' => route('domains.index'), 'icon' => 'link'],
            ['label' => 'Create database', 'sub' => 'Action', 'url' => route('databases.index'), 'icon' => 'circle-stack'],
            ['label' => 'Security center', 'sub' => 'Action', 'url' => route('security'), 'icon' => 'shield-check'],
            ['label' => 'Account settings', 'sub' => 'Action', 'url' => route('settings'), 'icon' => 'cog-6-tooth'],
        ];
    }

    public function render()
    {
        return view('livewire.command-palette', ['results' => $this->getResultsProperty()]);
    }
}
