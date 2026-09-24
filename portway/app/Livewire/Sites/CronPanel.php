<?php

namespace App\Livewire\Sites;

use App\Exceptions\QuotaExceededException;
use App\Models\CronJob;
use App\Models\Site;
use App\Services\Provisioning\CommandSanitizer;
use Livewire\Attributes\Locked;
use Livewire\Component;

class CronPanel extends Component
{
    #[Locked]
    public Site $site;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $label = '';

    public string $command = '';

    public string $preset = 'daily';

    public string $customSchedule = '';

    public ?int $deleteTargetId = null;

    public function mount(Site $site): void
    {
        $this->authorize('view', $site);
        $this->site = $site;
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'label', 'command', 'preset', 'customSchedule']);
        $this->preset = 'daily';
        $this->showForm = true;
    }

    public function openEdit(int $cronJobId): void
    {
        $cronJob = CronJob::where('site_id', $this->site->id)->findOrFail($cronJobId);
        $this->authorize('update', $cronJob);

        $this->editingId = $cronJob->id;
        $this->label = (string) $cronJob->label;
        $this->command = $cronJob->command;
        $this->preset = $cronJob->preset ?? 'custom';
        $this->customSchedule = $cronJob->preset ? '' : $cronJob->schedule;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'label' => ['nullable', 'string', 'max:100'],
            'command' => ['required', 'string', 'max:255'],
            'preset' => ['required', 'in:'.implode(',', array_merge(array_keys(CronJob::PRESETS), ['custom']))],
            'customSchedule' => ['required_if:preset,custom', 'nullable', 'string', 'max:100'],
        ]);

        if (! CommandSanitizer::isSafe($this->command)) {
            $this->addError('command', 'This command is not allowed by the security sandbox. See the allowed-programs list.');

            return;
        }

        $schedule = $this->preset === 'custom' ? trim($this->customSchedule) : CronJob::PRESETS[$this->preset];

        if (! \Cron\CronExpression::isValidExpression($schedule)) {
            $this->addError('customSchedule', 'That is not a valid cron schedule, e.g. "*/5 * * * *".');

            return;
        }

        if ($this->editingId) {
            $cronJob = CronJob::where('site_id', $this->site->id)->findOrFail($this->editingId);
            $this->authorize('update', $cronJob);

            $cronJob->update([
                'label' => $this->label ?: null,
                'command' => $this->command,
                'preset' => $this->preset === 'custom' ? null : $this->preset,
                'schedule' => $schedule,
            ]);

            $this->dispatch('toast', message: 'Cron job updated.', level: 'success');
        } else {
            $this->authorize('create', CronJob::class);

            $max = auth()->user()->max_cron_jobs;
            $used = CronJob::where('site_id', $this->site->id)->count();

            if ($used >= $max) {
                $this->addError('command', QuotaExceededException::cronJobLimit($max)->getMessage());

                return;
            }

            CronJob::create([
                'site_id' => $this->site->id,
                'user_id' => auth()->id(),
                'label' => $this->label ?: null,
                'command' => $this->command,
                'preset' => $this->preset === 'custom' ? null : $this->preset,
                'schedule' => $schedule,
                'is_active' => true,
            ]);

            $this->dispatch('toast', message: 'Cron job created.', level: 'success');
        }

        $this->showForm = false;
    }

    public function toggleActive(int $cronJobId): void
    {
        $cronJob = CronJob::where('site_id', $this->site->id)->findOrFail($cronJobId);
        $this->authorize('update', $cronJob);

        $cronJob->update(['is_active' => ! $cronJob->is_active]);
    }

    public function confirmDelete(int $cronJobId): void
    {
        $this->deleteTargetId = $cronJobId;
    }

    public function delete(): void
    {
        $cronJob = CronJob::where('site_id', $this->site->id)->findOrFail($this->deleteTargetId);
        $this->authorize('delete', $cronJob);

        $cronJob->delete();
        $this->deleteTargetId = null;
        $this->dispatch('toast', message: 'Cron job deleted.', level: 'success');
    }

    public function render()
    {
        return view('livewire.sites.cron-panel', [
            'cronJobs' => CronJob::where('site_id', $this->site->id)->latest()->get(),
        ]);
    }
}
