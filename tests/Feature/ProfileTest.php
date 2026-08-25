<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_staff_and_admin_can_also_access_their_own_profile_page(): void
    {
        // Previously customer-only by oversight - staff/admin had no route
        // to change their own password at all until this was fixed.
        $this->actingAs(User::factory()->staff()->create())->get('/profile')->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get('/profile')->assertOk();
    }

    public function test_admin_can_change_their_own_password(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->put('/profile/password', [
            'current_password' => 'password',
            'password' => 'a-new-secure-password',
            'password_confirmation' => 'a-new-secure-password',
        ]);

        $response->assertRedirect();
        $this->assertTrue(Hash::check('a-new-secure-password', $admin->fresh()->password));
    }

    public function test_customer_can_view_their_profile(): void
    {
        $customer = User::factory()->customer()->create(['name' => 'Dana Cruz']);

        $this->actingAs($customer)->get('/profile')->assertOk()->assertSee('Dana Cruz');
    }

    public function test_customer_can_update_their_profile(): void
    {
        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($customer)->put('/profile', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '0917-999-9999',
        ]);

        $response->assertRedirect();
        $this->assertSame('Updated Name', $customer->fresh()->name);
        $this->assertSame('updated@example.com', $customer->fresh()->email);
    }

    public function test_profile_email_must_be_unique(): void
    {
        $customer = User::factory()->customer()->create();
        $other = User::factory()->customer()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($customer)->put('/profile', [
            'name' => $customer->name,
            'email' => 'taken@example.com',
            'phone' => null,
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_customer_can_change_their_password_with_the_correct_current_password(): void
    {
        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($customer)->put('/profile/password', [
            'current_password' => 'password',
            'password' => 'a-new-secure-password',
            'password_confirmation' => 'a-new-secure-password',
        ]);

        $response->assertRedirect();
        $this->assertTrue(Hash::check('a-new-secure-password', $customer->fresh()->password));
    }

    public function test_password_change_requires_the_correct_current_password(): void
    {
        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($customer)->put('/profile/password', [
            'current_password' => 'wrong-password',
            'password' => 'a-new-secure-password',
            'password_confirmation' => 'a-new-secure-password',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('password', $customer->fresh()->password));
    }

    public function test_change_password_form_is_hidden_for_a_guest_booker_account(): void
    {
        $guest = User::factory()->customer()->guestBooker()->create();

        $response = $this->actingAs($guest)->get('/profile');

        $response->assertOk();
        $response->assertDontSee('Current Password');
        $response->assertSee('Email me a reset link');
    }

    public function test_change_password_form_shows_for_a_normal_customer(): void
    {
        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($customer)->get('/profile');

        $response->assertOk();
        $response->assertSee('Current Password');
    }
}
