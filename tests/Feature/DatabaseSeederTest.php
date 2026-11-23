<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketStatusChange;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_accounts_tickets_and_history(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertSame(3, User::count());
        $this->assertSame(40, Ticket::count());

        foreach (['user1@example.com', 'user2@example.com'] as $email) {
            $user = User::where('email', $email)->firstOrFail();
            $this->assertTrue(Hash::check('password', $user->password));

            $tickets = Ticket::where('user_id', $user->id)->get();
            $this->assertCount(20, $tickets);
            $this->assertCount(3, $tickets->pluck('status')->unique());
            $this->assertTrue($tickets->contains(fn ($t) => str_contains(mb_strtolower($t->title), 'принтер')));
            $this->assertTrue($tickets->pluck('title')->contains(fn ($t) => str_contains($t, 'ПРИНТЕР')));
        }

        $this->assertSame(20, Ticket::where('status', TicketStatus::New)->count());
        $this->assertSame(12, Ticket::where('status', TicketStatus::InProgress)->count());
        $this->assertSame(8, Ticket::where('status', TicketStatus::Done)->count());
    }

    public function test_every_non_new_ticket_has_admin_history_in_the_past_after_creation(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        Ticket::where('status', '!=', TicketStatus::New)->get()->each(function (Ticket $ticket) use ($admin) {
            $changes = TicketStatusChange::where('ticket_id', $ticket->id)->orderBy('id')->get();
            $this->assertNotEmpty($changes);
            $this->assertSame($ticket->status, $changes->last()->to_status);

            foreach ($changes as $change) {
                $this->assertSame($admin->id, $change->user_id);
                $this->assertTrue($change->created_at->gt($ticket->created_at));
                $this->assertTrue($change->created_at->isPast());
                $this->assertTrue($change->created_at->gte(now()->subDays(30)));
            }
        });

        $newIds = Ticket::where('status', TicketStatus::New)->pluck('id');
        $this->assertSame(0, TicketStatusChange::whereIn('ticket_id', $newIds)->count());
    }
}
