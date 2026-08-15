<?php

namespace Tests\Feature\Staff;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BusinessHour;
use App\Models\Court;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourtScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_access_the_court_schedule(): void
    {
        $this->actingAs(User::factory()->customer()->create())->get('/manage/courts/schedule')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/manage/courts/schedule')->assertRedirect('/login');
    }

    public function test_staff_and_admin_can_view_the_schedule(): void
    {
        $court = Court::factory()->create(['name' => 'Court 7']);
        $date = CarbonImmutable::now()->addDay();
        BusinessHour::updateOrCreate(
            ['day_of_week' => $date->dayOfWeek],
            ['opens_at' => '08:00:00', 'closes_at' => '20:00:00', 'is_closed' => false],
        );

        $this->actingAs(User::factory()->staff()->create())
            ->get("/manage/courts/schedule?court={$court->id}&date={$date->toDateString()}")
            ->assertOk()
            ->assertSee('Court 7')
            ->assertSee('Available');

        $this->actingAs(User::factory()->admin()->create())
            ->get("/manage/courts/schedule?court={$court->id}&date={$date->toDateString()}")
            ->assertOk();
    }

    public function test_a_booked_slot_shows_as_booked_with_a_link_to_the_booking(): void
    {
        $court = Court::factory()->create();
        $date = CarbonImmutable::now()->addDay();
        BusinessHour::updateOrCreate(
            ['day_of_week' => $date->dayOfWeek],
            ['opens_at' => '08:00:00', 'closes_at' => '20:00:00', 'is_closed' => false],
        );
        $booking = Booking::factory()->create([
            'court_id' => $court->id,
            'booking_date' => $date->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'status' => BookingStatus::Confirmed,
        ]);

        $response = $this->actingAs(User::factory()->staff()->create())
            ->get("/manage/courts/schedule?court={$court->id}&date={$date->toDateString()}");

        $response->assertOk();
        $response->assertSee('Booked');
        $response->assertSee(route('bookings.show', $booking));
    }

    public function test_shows_facility_closed_message_when_the_facility_is_closed(): void
    {
        $court = Court::factory()->create();
        $date = CarbonImmutable::now()->addDay();
        BusinessHour::updateOrCreate(
            ['day_of_week' => $date->dayOfWeek],
            ['is_closed' => true],
        );

        $response = $this->actingAs(User::factory()->staff()->create())
            ->get("/manage/courts/schedule?court={$court->id}&date={$date->toDateString()}");

        $response->assertOk();
        $response->assertSee('closed');
    }
}
