<?php

namespace App\Http\Controllers;

use App\Actions\ChangeTicketStatus;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TicketStatusController extends Controller
{
    public function __invoke(Request $request, ChangeTicketStatus $changeStatus, int $ticket): RedirectResponse
    {
        $ticket = Ticket::visibleTo($request->user())->findOrFail($ticket);

        Gate::authorize('changeStatus', $ticket);

        $data = $request->validate(['status' => ['required', Rule::enum(TicketStatus::class)]]);

        $changeStatus->handle($ticket, TicketStatus::from($data['status']), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Статус змінено')]);

        return back();
    }
}
