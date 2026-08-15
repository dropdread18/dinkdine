<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/booking-confirmed')->assertRedirect('/login');
    }

    public function test_renders_the_just_confirmed_booking_with_real_details(): void
    {
        $customer = User::factory()->customer()->create();
        $court = Court::factory()->create(['name' => 'Court 3']);
        $booking = Booking::factory()->create([
            'user_id' => $customer->id,
            'court_id' => $court->id,
            'price' => 300,
            'payment_status' => PaymentStatus::Paid,
        ]);

        $response = $this->actingAs($customer)
            ->withSession(['confirmed_booking_ids' => [$booking->id]])
            ->get('/booking-confirmed');

        $response->assertOk();
        $response->assertSee('Booking Confirmed');
        $response->assertSee('PB-'.$booking->id);
        $response->assertSee('Court 3');
        $response->assertSee('PAID');
        $response->assertSee('View My Booking');
    }

    public function test_multiple_bookings_show_a_bookings_link_instead_of_a_single_booking_link(): void
    {
        $customer = User::factory()->customer()->create();
        $bookings = Booking::factory()->count(2)->create(['user_id' => $customer->id]);

        $response = $this->actingAs($customer)
            ->withSession(['confirmed_booking_ids' => $bookings->pluck('id')->all()])
            ->get('/booking-confirmed');

        $response->assertOk();
        $response->assertSee('2 Bookings Confirmed');
        $response->assertSee('View My Bookings');
        foreach ($bookings as $booking) {
            $response->assertSee('PB-'.$booking->id);
        }
    }

    public function test_missing_session_data_redirects_to_my_bookings(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->get('/booking-confirmed')->assertRedirect(route('bookings.mine'));
    }

    public function test_cannot_view_another_customers_booking_via_a_stale_session_value(): void
    {
        $owner = User::factory()->customer()->create();
        $intruder = User::factory()->customer()->create();
        $booking = Booking::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($intruder)
            ->withSession(['confirmed_booking_ids' => [$booking->id]])
            ->get('/booking-confirmed')
            ->assertRedirect(route('bookings.mine'));
    }
}
