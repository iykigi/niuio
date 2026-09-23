<?php

namespace App\Livewire\Sites;

use App\Models\Site;
use App\Services\Provisioning\ProvisionerDriver;
use Livewire\Attributes\Locked;
use Livewire\Component;

class TerminalPanel extends Component
{
    #[Locked]
    public Site $site;

    public string $command = '';

    /** @var array<int, array{command: string, output: string, exit_code: int}> */
    public array $history = [];

    public function mount(Site $site): void
    {
        $this->authorize('useTerminal', $site);
        $this->site = $site;
    }

    public function run(ProvisionerDriver $driver): void
    {
        $this->authorize('useTerminal', $this->site);

        $command = trim($this->command);
        $this->command = '';

        if ($command === '') {
            return;
        }

        if (in_array($command, ['clear', 'cls'], true)) {
            $this->history = [];

            return;
        }

        try {
            $result = $driver->runCommand($this->site, $command, timeoutSeconds: 60);

            $this->history[] = [
                'command' => $command,
                'output' => trim($result['output']),
                'exit_code' => $result['exit_code'],
            ];
        } catch (\Throwable $e) {
            $this->history[] = [
                'command' => $command,
                'output' => $e->getMessage(),
                'exit_code' => 126,
            ];
        }

        // Keep the terminal buffer bounded so the DOM doesn't grow unbounded
        // over a long session.
        if (count($this->history) > 100) {
            $this->history = array_slice($this->history, -100);
        }
    }

    public function clear(): void
    {
        $this->history = [];
    }

    public function render()
    {
        return view('livewire.sites.terminal-panel');
    }
}
