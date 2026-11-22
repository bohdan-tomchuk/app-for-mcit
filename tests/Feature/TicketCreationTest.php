<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_creates_ticket_and_sees_success_flash(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tickets.store'), [
                'title' => 'Зламався принтер',
                'description' => 'Не друкує на другому поверсі',
                'priority' => 'high',
            ])
            ->assertRedirect(route('tickets.index', absolute: false))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Заявку створено']);

        $this->assertDatabaseHas('tickets', [
            'user_id' => $user->id,
            'title' => 'Зламався принтер',
            'priority' => TicketPriority::High->value,
            'status' => TicketStatus::New->value,
        ]);
    }

    public function test_priority_defaults_to_normal_and_injected_status_and_author_are_ignored(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)->post(route('tickets.store'), [
            'title' => 'Потрібен VPN',
            'description' => 'Для віддаленої роботи',
            'status' => 'done',
            'user_id' => $other->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('tickets', [
            'title' => 'Потрібен VPN',
            'user_id' => $user->id,
            'status' => 'new',
            'priority' => 'normal',
        ]);
    }

    public function test_invalid_fields_return_ukrainian_errors(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tickets.store'), [
                'title' => '   ',
                'description' => str_repeat('а', 5001),
                'priority' => 'urgent',
            ])
            ->assertSessionHasErrors([
                'title' => 'Поле назва є обов\'язковим для заповнення.',
                'description' => 'Текст в полі опис повинен мати довжину не більшу за 5000.',
                'priority',
            ]);

        $this->actingAs($user)
            ->post(route('tickets.store'), [
                'title' => str_repeat('а', 256),
                'description' => 'ok',
            ])
            ->assertSessionHasErrors(['title']);

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_admin_cannot_create_ticket(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('tickets.store'), ['title' => 'x', 'description' => 'y'])
            ->assertForbidden();

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_create_form_is_shown_to_user_only(): void
    {
        $this->actingAs(User::factory()->create())->get(route('tickets.create'))->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get(route('tickets.create'))->assertForbidden();
    }

    public function test_list_exposes_create_permission(): void
    {
        $this->actingAs(User::factory()->create())->get(route('tickets.index'))
            ->assertInertia(fn ($page) => $page->where('can.create', true));
        $this->actingAs(User::factory()->admin()->create())->get(route('tickets.index'))
            ->assertInertia(fn ($page) => $page->where('can.create', false));
    }

    public function test_status_enum_allows_only_forward_transitions(): void
    {
        $this->assertSame([TicketStatus::InProgress], TicketStatus::New->allowedTransitions());
        $this->assertSame([TicketStatus::Done], TicketStatus::InProgress->allowedTransitions());
        $this->assertSame([], TicketStatus::Done->allowedTransitions());
        $this->assertFalse(TicketStatus::New->canTransitionTo(TicketStatus::New));
        $this->assertSame('У роботі', TicketStatus::InProgress->label());
    }

    public function test_factory_has_in_progress_and_done_states(): void
    {
        $this->assertSame(TicketStatus::InProgress, Ticket::factory()->inProgress()->create()->status);
        $this->assertSame(TicketStatus::Done, Ticket::factory()->done()->create()->status);
    }
}
