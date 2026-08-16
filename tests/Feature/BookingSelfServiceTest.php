<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BusinessHour;
use App\Models\Court;
use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingSelfServiceTest extends TestCase
{
    use RefreshDatabase;

    private string $date;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('default_booking_duration_minutes', '60');
        Setting::set('min_booking_notice_minutes', '30');
        Setting::set('max_advance_booking_days', '30');
        Setting::set('cancellation_deadline_hours', '4');

        $this->date = CarbonImmutable::now()->addDays(2)->toDateString();

        BusinessHour::create([
            'day_of_week' => CarbonImmutable::parse($this->date)->dayOfWeek,
            'opens_at' => '08:00:00',
            'closes_at' => '20:00:00',
            'is_closed' => false,
        ]);
    }

    public function test_customer_can_cancel_their_own_eligible_booking(): void
    {
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create([
            'user_id' => $customer->id, 'booking_date' => $this->date, 'start_time' => '09:00:00', 'end_time' => '10:00:00',
        ]);

        $response = $this->actingAs($customer)->patch("/bookings/{$booking->id}/cancel");

        $response->assertRedirect();
        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
    }

    public function test_customer_cannot_cancel_someone_elses_booking(): void
    {
        $owner = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();
        $booking = Booking::factory()->create(['user_id' => $owner->id, 'booking_date' => $this->date]);

        $this->actingAs($other)->patch("/bookings/{$booking->id}/cancel")->assertNotFound();
        $this->assertNotEquals(BookingStatus::Cancelled, $booking->fresh()->status);
    }

    public function test_customer_cannot_cancel_a_booking_within_the_deadline(): void
    {
        $customer = User::factory()->customer()->create();
        $slotStart = CarbonImmutable::now()->startOfHour()->addHour();
        BusinessHour::updateOrCreate(
            ['day_of_week' => $slotStart->dayOfWeek],
            ['opens_at' => '00:00:00', 'closes_at' => '23:59:00', 'is_closed' => false],
        );
        $booking = Booking::factory()->create([
            'user_id' => $customer->id,
            'booking_date' => $slotStart->toDateString(),
            'start_time' => $slotStart->format('H:i:s'),
            'end_time' => $slotStart->addHour()->format('H:i:s'),
        ]);

        $response = $this->actingAs($customer)->patch("/bookings/{$booking->id}/cancel");

        $response->assertSessionHasErrors('booking');
        $this->assertNotEquals(BookingStatus::Cancelled, $booking->fresh()->status);
    }

    public function test_customer_can_view_their_reschedule_grid(): void
    {
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create(['user_id' => $customer->id, 'booking_date' => $this->date]);

        $this->actingAs($customer)
            ->get("/bookings/{$booking->id}/reschedule?date={$this->date}")
            ->assertOk()
            ->assertSee('Available');
    }

    public function test_customer_cannot_view_someone_elses_reschedule_grid(): void
    {
        $owner = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();
        $booking = Booking::factory()->create(['user_id' => $owner->id, 'booking_date' => $this->date]);

        $this->actingAs($other)->get("/bookings/{$booking->id}/reschedule")->assertNotFound();
    }

    public function test_customer_can_reschedule_their_own_booking(): void
    {
        $customer = User::factory()->customer()->create();
        $court = Court::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $customer->id, 'court_id' => $court->id,
            'booking_date' => $this->date, 'start_time' => '09:00:00', 'end_time' => '10:00:00',
        ]);

        $response = $this->actingAs($customer)->put("/bookings/{$booking->id}/reschedule/{$court->id}", [
            'date' => $this->date,
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
        ]);

        $response->assertRedirect(route('bookings.show', $booking));
        $this->assertSame('14:00:00', $booking->fresh()->start_time);
    }

    public function test_customer_cannot_reschedule_someone_elses_booking(): void
    {
        $owner = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();
        $court = Court::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $owner->id, 'court_id' => $court->id,
            'booking_date' => $this->date, 'start_time' => '09:00:00', 'end_time' => '10:00:00',
        ]);

        $this->actingAs($other)->put("/bookings/{$booking->id}/reschedule/{$court->id}", [
            'date' => $this->date, 'start_time' => '14:00:00', 'end_time' => '15:00:00',
        ])->assertNotFound();

        $this->assertSame('09:00:00', $booking->fresh()->start_time);
    }

    public function test_booking_detail_page_hides_actions_when_not_eligible(): void
    {
        $customer = User::factory()->customer()->create();
        $slotStart = CarbonImmutable::now()->startOfHour()->addHour();
        BusinessHour::updateOrCreate(
            ['day_of_week' => $slotStart->dayOfWeek],
            ['opens_at' => '00:00:00', 'closes_at' => '23:59:00', 'is_closed' => false],
        );
        $booking = Booking::factory()->create([
            'user_id' => $customer->id,
            'booking_date' => $slotStart->toDateString(),
            'start_time' => $slotStart->format('H:i:s'),
            'end_time' => $slotStart->addHour()->format('H:i:s'),
        ]);

        $response = $this->actingAs($customer)->get("/bookings/{$booking->id}");

        $response->assertOk();
        $response->assertDontSee('Cancel Booking');
        $response->assertSee('Too close to start time');
    }

    public function test_my_bookings_page_hides_actions_when_not_eligible(): void
    {
        $customer = User::factory()->customer()->create();
        $slotStart = CarbonImmutable::now()->startOfHour()->addHour();
        BusinessHour::updateOrCreate(
            ['day_of_week' => $slotStart->dayOfWeek],
            ['opens_at' => '00:00:00', 'closes_at' => '23:59:00', 'is_closed' => false],
        );
        Booking::factory()->create([
            'user_id' => $customer->id,
            'booking_date' => $slotStart->toDateString(),
            'start_time' => $slotStart->format('H:i:s'),
            'end_time' => $slotStart->addHour()->format('H:i:s'),
        ]);

        $response = $this->actingAs($customer)->get('/my-bookings');

        $response->assertOk();
        $response->assertDontSee('Cancel');
    }
}
