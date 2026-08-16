<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_users_can_authenticate_with_correct_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/');
    }

    public function test_admin_lands_on_the_admin_dashboard_after_login(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->post('/login', ['email' => $admin->email, 'password' => 'password']);

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_staff_lands_on_the_staff_dashboard_after_login(): void
    {
        $staff = User::factory()->staff()->create();

        $response = $this->post('/login', ['email' => $staff->email, 'password' => 'password']);

        $response->assertRedirect(route('staff.dashboard'));
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_a_disabled_account_cannot_authenticate(): void
    {
        $user = User::factory()->inactive()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_repeated_login_attempts_are_rate_limited(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
        }

        // The 6th attempt within the window should be throttled, even with
        // the correct password - this is what actually stops brute-forcing,
        // not the "wrong password" rejection itself.
        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertStatus(429);
        $this->assertGuest();
    }
}
