<?php

namespace App\Http\Controllers;

use App\Actions\UpdateTicket;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Http\Resources\TicketStatusChangeResource;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(TicketStatus::class)],
        ]);

        $tickets = Ticket::visibleTo($user)
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($user->isAdmin(), fn ($query) => $query->with('author:id,name'))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('tickets/Index', [
            'tickets' => TicketResource::collection($tickets),
            'isAdmin' => $user->isAdmin(),
            'can' => ['create' => $user->can('create', Ticket::class)],
            'filters' => ['status' => $filters['status'] ?? null],
            'statuses' => collect(TicketStatus::cases())
                ->map(fn (TicketStatus $s) => ['value' => $s->value, 'label' => $s->label()])
                ->all(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Ticket::class);

        return Inertia::render('tickets/Create', [
            'priorities' => $this->priorityOptions(),
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

    public function show(Request $request, int $ticket): Response
    {
        $user = $request->user();
        $ticket = Ticket::visibleTo($user)->with('author:id,name')->findOrFail($ticket);

        $history = $ticket->statusChanges()->with('actor:id,name')->orderBy('id')->get();

        return Inertia::render('tickets/Show', [
            'ticket' => TicketResource::make($ticket)->resolve(),
            'history' => TicketStatusChangeResource::collection($history)->resolve(),
            'can' => ['update' => $user->can('update', $ticket)],
            'transitions' => $user->can('changeStatus', $ticket)
                ? collect($ticket->status->allowedTransitions())
                    ->map(fn (TicketStatus $s) => ['value' => $s->value, 'label' => $s->label()])
                    ->all()
                : [],
        ]);
    }

    public function edit(Request $request, int $ticket): Response
    {
        $ticket = Ticket::visibleTo($request->user())->findOrFail($ticket);

        Gate::authorize('update', $ticket);

        return Inertia::render('tickets/Edit', [
            'ticket' => $ticket->only('id', 'title', 'description') + ['priority' => $ticket->priority->value],
            'priorities' => $this->priorityOptions(),
        ]);
    }

    public function update(UpdateTicketRequest $request, UpdateTicket $updateTicket): RedirectResponse
    {
        $ticket = $request->ticket();

        if (! $updateTicket->handle($ticket, array_filter($request->validated()), $request->user())) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Заявку вже взято в роботу, редагування недоступне')]);

            return to_route('tickets.show', $ticket);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Заявку оновлено')]);

        return to_route('tickets.show', $ticket);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function priorityOptions(): array
    {
        return collect(TicketPriority::cases())
            ->map(fn (TicketPriority $p) => ['value' => $p->value, 'label' => $p->label()])
            ->all();
    }
}
