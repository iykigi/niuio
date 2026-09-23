<?php

namespace App\Livewire\Admin;

use App\Models\SupportTicket;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class SupportInbox extends Component
{
    use WithPagination;

    public string $statusFilter = 'open';

    public string $assignedFilter = '';

    public function mount(): void
    {
        $this->authorize('support.view');
    }

    public function assignToMe(int $ticketId): void
    {
        $this->authorize('support.manage');

        SupportTicket::where('id', $ticketId)->update(['assigned_to' => Auth::id()]);
        $this->dispatch('toast', message: 'Ticket assigned to you.', level: 'success');
    }

    public function render()
    {
        $query = SupportTicket::with(['user', 'assignedTo']);

        if ($this->statusFilter === 'open') {
            $query->whereIn('status', ['open', 'pending', 'answered']);
        } elseif ($this->statusFilter === 'closed') {
            $query->where('status', 'closed');
        }

        if ($this->assignedFilter === 'me') {
            $query->where('assigned_to', Auth::id());
        } elseif ($this->assignedFilter === 'unassigned') {
            $query->whereNull('assigned_to');
        }

        return view('livewire.admin.support-inbox', [
            'tickets' => $query->orderByDesc('last_reply_at')->paginate(20),
        ])->title('Support inbox · Admin · Portway');
    }
}
