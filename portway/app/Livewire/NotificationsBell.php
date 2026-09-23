<?php

namespace App\Livewire;

use Livewire\Component;

class NotificationsBell extends Component
{
    public bool $open = false;

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function markRead(string $id): void
    {
        auth()->user()->notifications()->where('id', $id)->first()?->markAsRead();
    }

    public function render()
    {
        $notifications = auth()->user()->notifications()->latest()->limit(10)->get();

        return view('livewire.notifications-bell', [
            'notifications' => $notifications,
            'unreadCount' => auth()->user()->unreadNotifications()->count(),
        ]);
    }
}
