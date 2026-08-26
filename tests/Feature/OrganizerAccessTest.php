<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * An Open Play organizer is a restricted back-office role: it can see the
 * booking schedule and manage Open Play sessions, and nothing else - no
 * revenue/sales figures (Dashboard, Payments, Reports), no Settings, no
 * Customer/Staff management, no Walk-in/Check-in.
 */
class OrganizerAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_can_view_the_booking_schedule(): void
    {
        $this->actingAs(User::factory()->organizer()->create())
            ->get('/manage/bookings')
            ->assertOk();
    }

    public function test_organizer_can_manage_open_play(): void
    {
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)->get('/admin/open-play')->assertOk();
        $this->actingAs($organizer)->get('/admin/open-play/create')->assertOk();
    }

    public function test_organizer_cannot_see_sales_or_manage_the_facility(): void
    {
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($organizer)->get('/staff/dashboard')->assertForbidden();
        $this->actingAs($organizer)->get('/manage/payments')->assertForbidden();
        $this->actingAs($organizer)->get('/manage/reports')->assertForbidden();
        $this->actingAs($organizer)->get('/manage/settings')->assertForbidden();
        $this->actingAs($organizer)->get('/manage/walk-in')->assertForbidden();
        $this->actingAs($organizer)->get('/manage/check-in')->assertForbidden();
        $this->actingAs($organizer)->get('/admin/customers')->assertForbidden();
        $this->actingAs($organizer)->get('/admin/staff')->assertForbidden();
        $this->actingAs($organizer)->get('/admin/courts')->assertForbidden();
        $this->actingAs($organizer)->get('/admin/maintenance')->assertForbidden();
    }

    public function test_organizer_cannot_cancel_or_reschedule_bookings(): void
    {
        $booking = \App\Models\Booking::factory()->create();

        $this->actingAs(User::factory()->organizer()->create())
            ->patch("/manage/bookings/{$booking->id}/cancel")
            ->assertForbidden();
    }

    public function test_organizer_login_redirects_to_the_booking_schedule(): void
    {
        $organizer = User::factory()->organizer()->create();

        $response = $this->post('/login', [
            'email' => $organizer->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/manage/bookings');
    }

    public function test_admin_can_create_an_organizer_account(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/staff', [
            'role' => 'organizer',
            'name' => 'Ana Organizer',
            'email' => 'ana@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/admin/staff');
        $this->assertDatabaseHas('users', ['email' => 'ana@example.com', 'role' => UserRole::Organizer->value]);
    }
}
