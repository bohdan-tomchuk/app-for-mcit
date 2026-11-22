<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TicketShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_sees_own_ticket_with_all_fields(): void
    {
        $author = User::factory()->create(['name' => 'Автор Тест']);
        $ticket = Ticket::factory()->for($author, 'author')->create();

        $this->actingAs($author)->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('tickets/Show')
                ->where('ticket.id', $ticket->id)
                ->where('ticket.title', $ticket->title)
                ->where('ticket.description', $ticket->description)
                ->where('ticket.status', ['value' => 'new', 'label' => 'Нова'])
                ->where('ticket.priority', ['value' => 'normal', 'label' => 'Звичайний'])
                ->where('ticket.author', 'Автор Тест')
                ->has('ticket.created_at')
                ->has('ticket.updated_at'));
    }

    public function test_admin_sees_any_ticket(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('tickets.show', $ticket))
            ->assertOk();
    }

    public function test_foreign_ticket_is_404_without_leaking_data(): void
    {
        $ticket = Ticket::factory()->create(['title' => 'Секретна назва']);

        $this->actingAs(User::factory()->create())
            ->get(route('tickets.show', $ticket))
            ->assertNotFound()
            ->assertDontSee('Секретна назва');
    }

    public function test_missing_ticket_is_404_and_guest_is_redirected(): void
    {
        $this->actingAs(User::factory()->create())->get('/tickets/999')->assertNotFound();

        auth()->logout();
        $this->get(route('tickets.show', Ticket::factory()->create()))->assertRedirect(route('login'));
    }
}
