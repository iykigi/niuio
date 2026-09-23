<?php

namespace App\Livewire\Sites;

use App\Models\ActivityLog;
use App\Models\FileChangeEvent;
use App\Models\Site;
use Livewire\Attributes\Locked;
use Livewire\Component;

class SecurityPanel extends Component
{
    #[Locked]
    public Site $site;

    public string $filter = 'flagged';

    public function mount(Site $site): void
    {
        $this->authorize('view', $site);
        $this->site = $site;
    }

    public function markReviewed(int $eventId): void
    {
        $this->authorize('update', $this->site);

        $event = FileChangeEvent::where('site_id', $this->site->id)->findOrFail($eventId);
        $event->update(['flagged' => false]);

        $this->dispatch('toast', message: 'Marked as reviewed.', level: 'success');
    }

    public function render()
    {
        $changesQuery = FileChangeEvent::where('site_id', $this->site->id);

        if ($this->filter === 'flagged') {
            $changesQuery->where('flagged', true);
        }

        return view('livewire.sites.security-panel', [
            'changes' => $changesQuery->orderByDesc('detected_at')->limit(100)->get(),
            'flaggedCount' => FileChangeEvent::where('site_id', $this->site->id)->where('flagged', true)->count(),
            'activity' => ActivityLog::where('target_type', Site::class)
                ->where('target_id', $this->site->id)
                ->latest('id')
                ->limit(25)
                ->get(),
        ]);
    }
}
