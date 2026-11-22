<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function create(User $user): bool
    {
        return ! $user->isAdmin();
    }

    public function changeStatus(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin();
    }
}
