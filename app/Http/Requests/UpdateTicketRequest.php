<?php

namespace App\Http\Requests;

use App\Models\Ticket;

class UpdateTicketRequest extends StoreTicketRequest
{
    private ?Ticket $ticket = null;

    /**
     * Foreign tickets are 404 (before 403 and validation), own non-new ones are 403.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->ticket());
    }

    public function ticket(): Ticket
    {
        return $this->ticket ??= Ticket::visibleTo($this->user())->findOrFail((int) $this->route('ticket'));
    }
}
