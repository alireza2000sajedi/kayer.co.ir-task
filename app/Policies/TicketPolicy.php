<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Determine whether the user can create models.
     */
    public function create(User $user,Ticket $ticket): bool
    {
        return $ticket->user_id === $user->id;
    }
}
