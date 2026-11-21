<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketsLandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('tickets.index'))->assertRedirect(route('login'));
    }

    public function test_user_and_admin_can_open_tickets_list(): void
    {
        foreach (UserRole::cases() as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('tickets.index'))
                ->assertOk();
        }
    }

    public function test_admin_login_redirects_to_tickets_list(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('tickets.index', absolute: false));
    }
}
