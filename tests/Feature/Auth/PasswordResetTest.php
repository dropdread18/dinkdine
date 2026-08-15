<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $this->get('/forgot-password')->assertOk();
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) {
            $this->get('/reset-password/'.$notification->token)->assertOk();

            return true;
        });
    }

    public function test_password_can_be_reset_with_a_valid_token(): void
    {
        $user = User::factory()->create();

        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertRedirect('/login');

        $this->assertTrue(
            Hash::check('new-password', $user->fresh()->password)
        );
    }

    /**
     * A guest-checkout or walk-in account has an unknowable random password
     * and no way to learn it via Profile's "current password" field - the
     * password-reset flow is deliberately NOT guest-only so this works.
     */
    public function test_an_already_logged_in_user_can_complete_a_password_reset(): void
    {
        $customer = User::factory()->customer()->create();
        $token = Password::createToken($customer);

        $this->actingAs($customer)->get('/forgot-password')->assertOk();
        $this->actingAs($customer)->get('/reset-password/'.$token)->assertOk();

        $response = $this->actingAs($customer)->post('/reset-password', [
            'token' => $token,
            'email' => $customer->email,
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ]);

        $response->assertRedirect('/login');
        $this->assertTrue(Hash::check('a-brand-new-password', $customer->fresh()->password));
    }
}
