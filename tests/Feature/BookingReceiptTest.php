<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $booking = Booking::factory()->create();

        $this->get("/bookings/{$booking->id}/receipt")->assertRedirect('/login');
    }

    public function test_owner_can_view_their_receipt(): void
    {
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create(['user_id' => $customer->id]);
        Payment::factory()->create(['booking_id' => $booking->id, 'reference_number' => 'GCASH-REF-1']);

        $response = $this->actingAs($customer)->get("/bookings/{$booking->id}/receipt");

        $response->assertOk();
        $response->assertSee('PB-'.$booking->id);
        $response->assertSee('GCASH-REF-1');
    }

    public function test_another_customer_cannot_view_someone_elses_receipt(): void
    {
        $booking = Booking::factory()->create();
        $other = User::factory()->customer()->create();

        $this->actingAs($other)->get("/bookings/{$booking->id}/receipt")->assertNotFound();
    }

    public function test_staff_and_admin_can_view_any_receipt(): void
    {
        $booking = Booking::factory()->create();

        $this->actingAs(User::factory()->staff()->create())->get("/bookings/{$booking->id}/receipt")->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get("/bookings/{$booking->id}/receipt")->assertOk();
    }
}
