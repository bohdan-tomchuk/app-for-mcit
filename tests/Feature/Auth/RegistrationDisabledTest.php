<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationDisabledTest extends TestCase
{
    use RefreshDatabase;

    public function test_direct_registration_post_does_not_create_a_user(): void
    {
        $this->post('/register', [
            'name' => 'Intruder',
            'email' => 'intruder@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function test_registration_page_is_not_available(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_login_page_has_no_registration_link(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $this->assertStringNotContainsStringIgnoringCase(
            'register',
            file_get_contents(resource_path('js/pages/auth/Login.vue')),
        );
    }

    public function test_failed_login_returns_ukrainian_error_message(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'Вказані облікові дані не збігаються з нашими записами.',
        ]);
        $this->assertGuest();
    }
}
