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

    public function test_filters_by_status_and_keeps_param_in_pagination_links(): void
    {
        $user = User::factory()->create();
        Ticket::factory()->count(16)->for($user, 'author')->create(['status' => 'in_progress']);
        Ticket::factory()->for($user, 'author')->create(['status' => 'new']);

        $this->actingAs($user)->get(route('tickets.index', ['status' => 'in_progress']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('tickets.data', 15)
                ->where('tickets.meta.total', 16)
                ->where('filters.status', 'in_progress')
                ->where('tickets.data.0.status.value', 'in_progress')
                ->where('tickets.links.next', fn ($url) => str_contains($url, 'status=in_progress')));
    }

    public function test_unknown_status_fails_validation(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('tickets.index', ['status' => 'bogus']))
            ->assertSessionHasErrors('status');
    }

    public function test_filter_never_exposes_foreign_tickets(): void
    {
        $user = User::factory()->create();
        Ticket::factory()->create(['status' => 'new']);

        $this->actingAs($user)->get(route('tickets.index', ['status' => 'new']))
            ->assertInertia(fn (Assert $page) => $page->has('tickets.data', 0));
    }

    public function test_search_is_case_insensitive_including_cyrillic(): void
    {
        $user = User::factory()->create();
        $match = Ticket::factory()->for($user, 'author')->create(['title' => 'Не працює принтер у бухгалтерії']);
        Ticket::factory()->for($user, 'author')->create(['title' => 'Зламався монітор']);

        $this->actingAs($user)->get(route('tickets.index', ['search' => 'ПРИНТЕР']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('tickets.data', 1)
                ->where('tickets.data.0.id', $match->id)
                ->where('filters.search', 'ПРИНТЕР'));
    }

    public function test_search_treats_wildcards_literally(): void
    {
        $user = User::factory()->create();
        $match = Ticket::factory()->for($user, 'author')->create(['title' => 'Знижка 50% на а_б\\в']);
        Ticket::factory()->for($user, 'author')->create(['title' => 'Знижка 500 на аxбв']);

        foreach (['50%', 'а_б', '\\'] as $term) {
            $this->actingAs($user)->get(route('tickets.index', ['search' => $term]))
                ->assertInertia(fn (Assert $page) => $page
                    ->has('tickets.data', 1)
                    ->where('tickets.data.0.id', $match->id));
        }
    }

    public function test_search_combines_with_status_and_visibility_and_keeps_pagination_links(): void
    {
        $user = User::factory()->create();
        Ticket::factory()->count(16)->for($user, 'author')->create(['title' => 'Принтер', 'status' => 'new']);
        Ticket::factory()->for($user, 'author')->create(['title' => 'Принтер', 'status' => 'done']);
        Ticket::factory()->create(['title' => 'Принтер', 'status' => 'new']);

        $this->actingAs($user)->get(route('tickets.index', ['search' => 'ПРИНТЕР', 'status' => 'new']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('tickets.data', 15)
                ->where('tickets.meta.total', 16)
                ->where('tickets.links.next', fn ($url) => str_contains($url, 'status=new')
                    && str_contains($url, 'search='.rawurlencode('ПРИНТЕР'))));
    }
}
