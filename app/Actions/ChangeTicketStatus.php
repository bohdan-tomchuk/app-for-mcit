<?php

namespace App\Actions;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketStatusChange;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeTicketStatus
{
    /**
     * Re-reads the ticket under a row lock so concurrent requests run in turn.
     *
     * @throws ValidationException
     */
    public function handle(Ticket $ticket, TicketStatus $target, User $actor): void
    {
        DB::transaction(function () use ($ticket, $target, $actor) {
            $locked = Ticket::lockForUpdate()->findOrFail($ticket->id);

            if (! $locked->status->canTransitionTo($target)) {
                throw ValidationException::withMessages([
                    'status' => __('Перехід зі статусу «:from» у «:to» неможливий', [
                        'from' => $locked->status->label(),
                        'to' => $target->label(),
                    ]),
                ]);
            }

            $from = $locked->status;
            $locked->status = $target;
            $locked->save();

            TicketStatusChange::create([
                'ticket_id' => $locked->id,
                'from_status' => $from,
                'to_status' => $target,
                'user_id' => $actor->id,
            ]);
        });
    }
}
