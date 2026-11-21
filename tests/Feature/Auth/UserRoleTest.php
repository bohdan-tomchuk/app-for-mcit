<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_defaults_to_user_role(): void
    {
        $user = User::factory()->create()->fresh();

        $this->assertSame(UserRole::User, $user->role);
        $this->assertFalse($user->isAdmin());
    }

    public function test_admin_factory_state_creates_admin(): void
    {
        $user = User::factory()->admin()->create()->fresh();

        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertTrue($user->isAdmin());
    }

    public function test_role_in_profile_update_request_is_ignored(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Test User',
                'email' => $user->email,
                'role' => UserRole::Admin->value,
            ])
            ->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame(UserRole::User, $user->role);
    }
}
