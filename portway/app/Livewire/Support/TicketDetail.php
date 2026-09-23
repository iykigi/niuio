<?php

namespace App\Livewire\Support;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class TicketDetail extends Component
{
    public SupportTicket $ticket;

    public string $reply = '';

    public function mount(SupportTicket $ticket): void
    {
        $this->authorize('view', $ticket);
        $this->ticket = $ticket;
    }

    public function sendReply(): void
    {
        $this->authorize('reply', $this->ticket);

        $this->validate(['reply' => ['required', 'string', 'min:2', 'max:5000']]);

        $isStaff = Auth::user()->isStaff();

        SupportMessage::create([
            'support_ticket_id' => $this->ticket->id,
            'user_id' => Auth::id(),
            'is_staff_reply' => $isStaff,
            'body' => $this->reply,
        ]);

        $this->ticket->update([
            'status' => $isStaff ? 'answered' : 'pending',
            'last_reply_at' => now(),
        ]);

        $this->reply = '';
        $this->dispatch('toast', message: 'Reply sent.', level: 'success');
    }

    public function close(): void
    {
        $this->authorize('reply', $this->ticket);
        $this->ticket->update(['status' => 'closed']);
        $this->dispatch('toast', message: 'Ticket closed.', level: 'success');
    }

    public function reopen(): void
    {
        $this->authorize('reply', $this->ticket);
        $this->ticket->update(['status' => 'open']);
        $this->dispatch('toast', message: 'Ticket reopened.', level: 'success');
    }

    public function render()
    {
        return view('livewire.support.ticket-detail', [
            'messages' => $this->ticket->messages()->with('user')->get(),
        ])->title("{$this->ticket->subject} · Support · Portway");
    }
}
