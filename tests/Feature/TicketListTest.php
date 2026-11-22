<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TicketListTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_sees_only_own_tickets_without_author(): void
    {
        $user = User::factory()->create();
        $mine = Ticket::factory()->for($user, 'author')->create();
        Ticket::factory()->create();

        $this->actingAs($user)->get(route('tickets.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('tickets/Index')
                ->has('tickets.data', 1)
                ->where('tickets.data.0.id', $mine->id)
                ->where('tickets.data.0.status', ['value' => 'new', 'label' => 'Нова'])
                ->where('tickets.data.0.priority', ['value' => 'normal', 'label' => 'Звичайний'])
                ->missing('tickets.data.0.author')
                ->missing('tickets.data.0.description')
                ->missing('tickets.data.0.user_id')
                ->where('can.create', true)
                ->where('isAdmin', false));
    }

    public function test_admin_sees_all_tickets_with_author_and_cannot_create(): void
    {
        $author = User::factory()->create(['name' => 'Автор Тест']);
        Ticket::factory()->for($author, 'author')->create();
        Ticket::factory()->create();

        $this->actingAs(User::factory()->admin()->create())->get(route('tickets.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('tickets.data', 2)
                ->where('tickets.data.1.author', 'Автор Тест')
                ->where('can.create', false)
                ->where('isAdmin', true));
    }

    public function test_orders_by_id_desc_and_paginates_by_15(): void
    {
        $user = User::factory()->create();
        Ticket::factory()->count(16)->for($user, 'author')->create();
        $newest = Ticket::max('id');

        $this->actingAs($user)->get(route('tickets.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('tickets.data', 15)
                ->where('tickets.data.0.id', $newest)
                ->where('tickets.meta.total', 16)
                ->where('tickets.meta.last_page', 2));

        $this->actingAs($user)->get(route('tickets.index', ['page' => 2]))
            ->assertInertia(fn (Assert $page) => $page->has('tickets.data', 1));
    }

    public function test_user_without_tickets_gets_empty_list_and_can_create(): void
    {
        Ticket::factory()->create();

        $this->actingAs(User::factory()->create())->get(route('tickets.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('tickets.data', 0)
                ->where('can.create', true));
    }
}
