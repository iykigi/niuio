<?php

namespace App\Livewire\Support;

use App\Models\Site;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class TicketsIndex extends Component
{
    public bool $showCreate = false;

    public string $subject = '';

    public string $category = 'technical';

    public string $priority = 'normal';

    public ?int $siteId = null;

    public string $body = '';

    public string $filter = 'open';

    public function openCreate(): void
    {
        $this->reset(['subject', 'category', 'priority', 'siteId', 'body']);
        $this->category = 'technical';
        $this->priority = 'normal';
        $this->showCreate = true;
    }

    public function create(): void
    {
        $this->authorize('create', SupportTicket::class);

        $this->validate([
            'subject' => ['required', 'string', 'max:150'],
            'category' => ['required', 'in:billing_free_tier,technical,abuse_report,domain,account,other'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'siteId' => ['nullable', 'integer'],
            'body' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        if ($this->siteId) {
            Site::where('user_id', Auth::id())->findOrFail($this->siteId);
        }

        $ticket = SupportTicket::create([
            'user_id' => Auth::id(),
            'site_id' => $this->siteId,
            'subject' => $this->subject,
            'category' => $this->category,
            'priority' => $this->priority,
            'status' => 'open',
            'last_reply_at' => now(),
        ]);

        SupportMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'is_staff_reply' => false,
            'body' => $this->body,
        ]);

        $this->showCreate = false;
        $this->redirectRoute('support.show', $ticket, navigate: true);
    }

    public function render()
    {
        $query = SupportTicket::where('user_id', Auth::id());

        if ($this->filter === 'open') {
            $query->whereIn('status', ['open', 'pending', 'answered']);
        } elseif ($this->filter === 'closed') {
            $query->where('status', 'closed');
        }

        return view('livewire.support.tickets-index', [
            'tickets' => $query->latest('last_reply_at')->get(),
            'sites' => Auth::user()->sites()->orderBy('name')->get(),
        ])->title('Support · Portway');
    }
}
