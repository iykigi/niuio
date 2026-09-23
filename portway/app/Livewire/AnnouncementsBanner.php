<?php

namespace App\Livewire;

use App\Models\Announcement;
use App\Models\AnnouncementDismissal;
use Livewire\Component;

class AnnouncementsBanner extends Component
{
    public ?int $announcementId = null;

    public function mount(): void
    {
        $dismissed = AnnouncementDismissal::where('user_id', auth()->id())->pluck('announcement_id');

        $announcement = Announcement::query()
            ->where('is_published', true)
            ->whereNotIn('id', $dismissed)
            ->get()
            ->first(fn (Announcement $a) => $a->isCurrentlyActive());

        $this->announcementId = $announcement?->id;
    }

    public function dismiss(): void
    {
        if ($this->announcementId) {
            AnnouncementDismissal::create([
                'announcement_id' => $this->announcementId,
                'user_id' => auth()->id(),
                'dismissed_at' => now(),
            ]);
        }

        $this->announcementId = null;
    }

    public function render()
    {
        return view('livewire.announcements-banner', [
            'announcement' => $this->announcementId ? Announcement::find($this->announcementId) : null,
        ]);
    }
}
