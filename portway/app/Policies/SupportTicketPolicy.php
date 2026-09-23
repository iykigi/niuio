<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SupportTicket $ticket): bool
    {
        return $user->id === $ticket->user_id || $user->can('support.view');
    }

    public function create(User $user): bool
    {
        return true; // even suspended accounts may reach support.
    }

    public function reply(User $user, SupportTicket $ticket): bool
    {
        return $user->id === $ticket->user_id || $user->can('support.manage');
    }

    public function update(User $user, SupportTicket $ticket): bool
    {
        return $user->can('support.manage');
    }
}
