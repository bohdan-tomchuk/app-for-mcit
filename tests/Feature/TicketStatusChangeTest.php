<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketStatusChange;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class TicketStatusChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_moves_ticket_through_all_statuses_with_history(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($admin)->patch(route('tickets.status', $ticket), ['status' => 'in_progress'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->actingAs($admin)->patch(route('tickets.status', $ticket), ['status' => 'done'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(TicketStatus::Done, $ticket->fresh()->status);

        $history = TicketStatusChange::where('ticket_id', $ticket->id)->orderBy('id')->get();
        $this->assertCount(2, $history);
        $this->assertSame([TicketStatus::New, TicketStatus::InProgress], [$history[0]->from_status, $history[0]->to_status]);
        $this->assertSame([TicketStatus::InProgress, TicketStatus::Done], [$history[1]->from_status, $history[1]->to_status]);
        $this->assertSame([$admin->id, $admin->id], $history->pluck('user_id')->all());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function forbiddenTransitions(): array
    {
        return [
            'new to done' => ['new', 'done'],
            'in_progress to new' => ['in_progress', 'new'],
            'done to in_progress' => ['done', 'in_progress'],
            'done to new' => ['done', 'new'],
            'new to new' => ['new', 'new'],
        ];
    }

    #[DataProvider('forbiddenTransitions')]
    public function test_forbidden_transition_is_rejected_without_changes(string $from, string $to): void
    {
        $ticket = Ticket::factory()->create(['status' => $from]);

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('tickets.status', $ticket), ['status' => $to])
            ->assertSessionHasErrors('status');

        $this->assertSame($from, $ticket->fresh()->status->value);
        $this->assertSame(0, TicketStatusChange::count());
    }

    public function test_unknown_status_is_rejected(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('tickets.status', $ticket), ['status' => 'bogus'])
            ->assertSessionHasErrors('status');
    }

    public function test_user_gets_403_on_own_ticket_and_404_on_foreign(): void
    {
        $user = User::factory()->create();
        $own = Ticket::factory()->for($user, 'author')->create();
        $foreign = Ticket::factory()->create();

        $this->actingAs($user)->patch(route('tickets.status', $own), ['status' => 'in_progress'])->assertForbidden();
        $this->actingAs($user)->patch(route('tickets.status', $foreign), ['status' => 'in_progress'])->assertNotFound();

        $this->assertSame(TicketStatus::New, $own->fresh()->status);
        $this->assertSame(TicketStatus::New, $foreign->fresh()->status);
        $this->assertSame(0, TicketStatusChange::count());
    }

    public function test_failed_history_write_rolls_back_status(): void
    {
        $ticket = Ticket::factory()->create();
        TicketStatusChange::creating(fn () => throw new RuntimeException('boom'));

        $this->actingAs(User::factory()->admin()->create())
            ->withoutExceptionHandling()
            ->expectException(RuntimeException::class);

        try {
            $this->patch(route('tickets.status', $ticket), ['status' => 'in_progress']);
        } finally {
            $this->assertSame(TicketStatus::New, $ticket->fresh()->status);
            $this->assertSame(0, TicketStatusChange::count());
        }
    }

    public function test_page_shows_transitions_to_admin_only_and_history(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = Ticket::factory()->create();
        $this->actingAs($admin)->patch(route('tickets.status', $ticket), ['status' => 'in_progress']);

        $this->actingAs($admin)->get(route('tickets.show', $ticket))
            ->assertInertia(fn (Assert $page) => $page
                ->where('transitions', [['value' => 'done', 'label' => 'Завершена']])
                ->has('history', 1)
                ->where('history.0.from_status.value', 'new')
                ->where('history.0.to_status.value', 'in_progress')
                ->where('history.0.actor', $admin->name)
                ->has('history.0.created_at'));

        $this->actingAs($ticket->author)->get(route('tickets.show', $ticket))
            ->assertInertia(fn (Assert $page) => $page
                ->where('transitions', [])
                ->has('history', 1));

        $ticket->forceFill(['status' => TicketStatus::Done])->save();
        $this->actingAs($admin)->get(route('tickets.show', $ticket))
            ->assertInertia(fn (Assert $page) => $page->where('transitions', []));
    }
}
