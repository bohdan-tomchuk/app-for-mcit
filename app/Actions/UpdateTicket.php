<?php

namespace App\Actions;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateTicket
{
    /**
     * Re-reads the ticket under a row lock and re-checks the policy on it,
     * so editing and status changes of one ticket never interleave.
     *
     * @param  array<string, mixed>  $data
     * @return bool false when editing is no longer allowed
     */
    public function handle(Ticket $ticket, array $data, User $actor): bool
    {
        return DB::transaction(function () use ($ticket, $data, $actor) {
            $locked = Ticket::lockForUpdate()->findOrFail($ticket->id);

            if ($actor->cannot('update', $locked)) {
                return false;
            }

            $locked->update($data);

            return true;
        });
    }
}
