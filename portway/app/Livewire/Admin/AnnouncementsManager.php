<?php

namespace App\Livewire\Admin;

use App\Models\Announcement;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AnnouncementsManager extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $body = '';

    public string $type = 'general';

    public string $severity = 'info';

    public ?string $startsAt = null;

    public ?string $endsAt = null;

    public bool $isPublished = true;

    public ?int $deleteTargetId = null;

    public function mount(): void
    {
        $this->authorize('announcements.view');
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'title', 'body', 'startsAt', 'endsAt']);
        $this->type = 'general';
        $this->severity = 'info';
        $this->isPublished = true;
        $this->showForm = true;
    }

    public function openEdit(int $announcementId): void
    {
        $announcement = Announcement::findOrFail($announcementId);

        $this->editingId = $announcement->id;
        $this->title = $announcement->title;
        $this->body = $announcement->body;
        $this->type = $announcement->type;
        $this->severity = $announcement->severity;
        $this->startsAt = $announcement->starts_at?->format('Y-m-d\TH:i');
        $this->endsAt = $announcement->ends_at?->format('Y-m-d\TH:i');
        $this->isPublished = $announcement->is_published;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('announcements.manage');

        $this->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:2000'],
            'type' => ['required', 'in:maintenance,feature,security,general'],
            'severity' => ['required', 'in:info,warning,critical'],
            'startsAt' => ['nullable', 'date'],
            'endsAt' => ['nullable', 'date', 'after_or_equal:startsAt'],
        ]);

        $data = [
            'title' => $this->title,
            'body' => $this->body,
            'type' => $this->type,
            'severity' => $this->severity,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
            'is_published' => $this->isPublished,
        ];

        if ($this->editingId) {
            Announcement::findOrFail($this->editingId)->update($data);
            $this->dispatch('toast', message: 'Announcement updated.', level: 'success');
        } else {
            Announcement::create([...$data, 'created_by' => Auth::id()]);
            $this->dispatch('toast', message: 'Announcement published.', level: 'success');
        }

        $this->showForm = false;
    }

    public function togglePublished(int $announcementId): void
    {
        $this->authorize('announcements.manage');

        $announcement = Announcement::findOrFail($announcementId);
        $announcement->update(['is_published' => ! $announcement->is_published]);
    }

    public function confirmDelete(int $announcementId): void
    {
        $this->deleteTargetId = $announcementId;
    }

    public function delete(): void
    {
        $this->authorize('announcements.manage');

        Announcement::where('id', $this->deleteTargetId)->delete();
        $this->deleteTargetId = null;
        $this->dispatch('toast', message: 'Announcement deleted.', level: 'success');
    }

    public function render()
    {
        return view('livewire.admin.announcements-manager', [
            'announcements' => Announcement::with('createdBy')->latest()->get(),
        ])->title('Announcements · Admin · Portway');
    }
}
