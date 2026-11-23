<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TicketEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_edits_own_new_ticket_and_status_and_author_are_ignored(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $ticket = Ticket::factory()->for($user, 'author')->create();

        $this->actingAs($user)
            ->put(route('tickets.update', $ticket), [
                'title' => 'Нова назва',
                'description' => 'Новий опис',
                'priority' => 'high',
                'status' => 'done',
                'user_id' => $other->id,
            ])
            ->assertRedirect(route('tickets.show', $ticket, absolute: false))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Заявку оновлено']);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'user_id' => $user->id,
            'title' => 'Нова назва',
            'description' => 'Новий опис',
            'priority' => TicketPriority::High->value,
            'status' => TicketStatus::New->value,
        ]);
    }

    public function test_edit_form_is_prefilled_and_show_offers_edit_only_when_allowed(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->for($user, 'author')->create(['title' => 'Принтер']);

        $this->actingAs($user)->get(route('tickets.edit', $ticket))
            ->assertInertia(fn (Assert $page) => $page
                ->component('tickets/Edit')
                ->where('ticket.title', 'Принтер'));

        $this->actingAs($user)->get(route('tickets.show', $ticket))
            ->assertInertia(fn (Assert $page) => $page->where('can.update', true));

        $this->actingAs(User::factory()->admin()->create())->get(route('tickets.show', $ticket))
            ->assertInertia(fn (Assert $page) => $page->where('can.update', false));

        $ticket->forceFill(['status' => TicketStatus::InProgress])->save();

        $this->actingAs($user)->get(route('tickets.show', $ticket))
            ->assertInertia(fn (Assert $page) => $page->where('can.update', false));
    }

    public function test_author_cannot_edit_ticket_that_is_not_new(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->inProgress()->for($user, 'author')->create(['title' => 'Старе']);

        $this->actingAs($user)->get(route('tickets.edit', $ticket))->assertForbidden();
        $this->actingAs($user)
            ->put(route('tickets.update', $ticket), ['title' => 'Нове', 'description' => 'x'])
            ->assertForbidden();

        $this->assertSame('Старе', $ticket->fresh()->title);
    }

    public function test_admin_cannot_edit_ticket_text(): void
    {
        $ticket = Ticket::factory()->create(['title' => 'Старе']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('tickets.update', $ticket), ['title' => 'Нове', 'description' => 'x'])
            ->assertForbidden();

        $this->assertSame('Старе', $ticket->fresh()->title);
    }

    public function test_user_gets_404_for_foreign_ticket_even_with_invalid_input(): void
    {
        $ticket = Ticket::factory()->create(['title' => 'Старе']);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('tickets.edit', $ticket))->assertNotFound();
        $this->actingAs($user)
            ->put(route('tickets.update', $ticket), ['title' => 'Нове', 'description' => 'x'])
            ->assertNotFound();
        $this->actingAs($user)->put(route('tickets.update', $ticket), [])->assertNotFound();

        $this->assertSame('Старе', $ticket->fresh()->title);
    }

    public function test_update_validates_input(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->for($user, 'author')->create();

        $this->actingAs($user)
            ->put(route('tickets.update', $ticket), ['title' => '', 'description' => ''])
            ->assertSessionHasErrors(['title', 'description']);
    }

    public function test_falsy_string_values_are_saved_on_update(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->for($user, 'author')->create();

        $this->actingAs($user)
            ->put(route('tickets.update', $ticket), ['title' => '0', 'description' => '0'])
            ->assertRedirect(route('tickets.show', $ticket, absolute: false));

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'title' => '0', 'description' => '0']);
    }
}
