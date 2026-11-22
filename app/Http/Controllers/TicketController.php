<?php

namespace App\Http\Controllers;

use App\Enums\TicketPriority;
use App\Http\Requests\StoreTicketRequest;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('tickets/Index', [
            'can' => ['create' => $request->user()->can('create', Ticket::class)],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Ticket::class);

        return Inertia::render('tickets/Create', [
            'priorities' => collect(TicketPriority::cases())
                ->map(fn (TicketPriority $p) => ['value' => $p->value, 'label' => $p->label()])
                ->all(),
        ]);
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $ticket = new Ticket(array_filter($request->validated()));
        $ticket->user_id = $request->user()->id;
        $ticket->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Заявку створено')]);

        return to_route('tickets.index');
    }
}
