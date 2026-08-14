<?php

namespace Tests\Feature\Staff;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BusinessHour;
use App\Models\Court;
use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingManagementTest extends TestCase
{
    use RefreshDatabase;

    private string $date;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('default_booking_duration_minutes', '60');
        Setting::set('min_booking_notice_minutes', '30');
        Setting::set('max_advance_booking_days', '30');

        $this->date = CarbonImmutable::now()->addDays(2)->toDateString();

        BusinessHour::create([
            'day_of_week' => CarbonImmutable::parse($this->date)->dayOfWeek,
            'opens_at' => '08:00:00',
            'closes_at' => '20:00:00',
            'is_closed' => false,
        ]);
    }

    public function test_customer_cannot_access_the_booking_management_list(): void
    {
        $this->actingAs(User::factory()->customer()->create())->get('/manage/bookings')->assertForbidden();
    }

    public function test_staff_and_admin_can_view_the_bookings_list(): void
    {
        $booking = Booking::factory()->create(['booking_date' => $this->date]);

        $this->actingAs(User::factory()->staff()->create())
            ->get('/manage/bookings')
            ->assertOk()
            ->assertSee($booking->user->name);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/manage/bookings')
            ->assertOk()
            ->assertSee($booking->user->name);
    }

    public function test_bookings_can_be_filtered_by_status(): void
    {
        $confirmed = Booking::factory()->create(['booking_date' => $this->date]);
        $cancelled = Booking::factory()->cancelled()->create(['booking_date' => $this->date]);

        $response = $this->actingAs(User::factory()->staff()->create())
            ->get('/manage/bookings?status=cancelled');

        $response->assertSee($cancelled->user->name);
        $response->assertDontSee($confirmed->user->name);
    }

    public function test_bookings_can_be_searched_by_customer_name(): void
    {
        $target = Booking::factory()->create(['booking_date' => $this->date]);
        $target->user->update(['name' => 'Juan Dela Cruz']);
        $other = Booking::factory()->create(['booking_date' => $this->date]);

        $response = $this->actingAs(User::factory()->staff()->create())
            ->get('/manage/bookings?q=Juan');

        $response->assertSee('Juan Dela Cruz');
        $response->assertDontSee($other->user->name);
    }

    public function test_bookings_can_be_searched_by_booking_reference(): void
    {
        $target = Booking::factory()->create(['booking_date' => $this->date]);
        $other = Booking::factory()->create(['booking_date' => $this->date]);

        $response = $this->actingAs(User::factory()->staff()->create())
            ->get('/manage/bookings?q=PB-'.$target->id);

        $response->assertSee($target->user->name);
        $response->assertDontSee($other->user->name);
    }

    public function test_staff_can_cancel_a_booking(): void
    {
        $booking = Booking::factory()->create(['booking_date' => $this->date]);

        $response = $this->actingAs(User::factory()->staff()->create())
            ->patch("/manage/bookings/{$booking->id}/cancel", ['reason' => 'Customer no-show']);

        $response->assertRedirect();
        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
        $this->assertStringContainsString('Customer no-show', $booking->fresh()->notes);
    }

    public function test_cancelling_an_already_cancelled_booking_shows_an_error(): void
    {
        $booking = Booking::factory()->cancelled()->create(['booking_date' => $this->date]);

        $response = $this->actingAs(User::factory()->staff()->create())
            ->patch("/manage/bookings/{$booking->id}/cancel");

        $response->assertSessionHasErrors('booking');
    }

    public function test_staff_can_view_the_reschedule_grid(): void
    {
        $booking = Booking::factory()->create(['booking_date' => $this->date]);

        $this->actingAs(User::factory()->staff()->create())
            ->get("/manage/bookings/{$booking->id}/reschedule?date={$this->date}")
            ->assertOk()
            ->assertSee('Available');
    }

    public function test_staff_can_reschedule_a_booking_to_a_new_slot(): void
    {
        $court = Court::factory()->create();
        $booking = Booking::factory()->create([
            'court_id' => $court->id,
            'booking_date' => $this->date,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);

        $response = $this->actingAs(User::factory()->staff()->create())
            ->put("/manage/bookings/{$booking->id}/reschedule/{$court->id}", [
                'date' => $this->date,
                'start_time' => '14:00:00',
                'end_time' => '15:00:00',
            ]);

        $response->assertRedirect(route('bookings.show', $booking));
        $this->assertSame('14:00:00', $booking->fresh()->start_time);
    }

    public function test_reschedule_to_an_already_taken_slot_fails_gracefully(): void
    {
        $court = Court::factory()->create();
        $booking = Booking::factory()->create([
            'court_id' => $court->id, 'booking_date' => $this->date, 'start_time' => '09:00:00', 'end_time' => '10:00:00',
        ]);
        Booking::factory()->create([
            'court_id' => $court->id, 'booking_date' => $this->date, 'start_time' => '14:00:00', 'end_time' => '15:00:00',
        ]);

        $response = $this->actingAs(User::factory()->staff()->create())
            ->put("/manage/bookings/{$booking->id}/reschedule/{$court->id}", [
                'date' => $this->date,
                'start_time' => '14:00:00',
                'end_time' => '15:00:00',
            ]);

        $response->assertSessionHasErrors('booking');
        $this->assertSame('09:00:00', $booking->fresh()->start_time);
    }
}
