<?php

namespace Tests\Feature\Staff;

use App\Enums\BookingSource;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\BusinessHour;
use App\Models\Court;
use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalkInBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('default_booking_duration_minutes', '60');
        Setting::set('min_booking_notice_minutes', '30');
        Setting::set('max_advance_booking_days', '30');

        BusinessHour::updateOrCreate(
            ['day_of_week' => CarbonImmutable::now()->dayOfWeek],
            ['opens_at' => '00:00:00', 'closes_at' => '23:59:00', 'is_closed' => false],
        );
    }

    /**
     * The next full hour - but never 23:00 today, since AvailabilityService
     * never generates a slot ending at "00:00:00" (see the identical helper
     * and full explanation in BookingServiceTest::soonSlot()).
     */
    private function soonSlot(): CarbonImmutable
    {
        $slotStart = CarbonImmutable::now()->startOfHour();

        return $slotStart->hour === 23 ? $slotStart->addHour() : $slotStart;
    }

    public function test_customer_cannot_access_walk_in_booking(): void
    {
        $this->actingAs(User::factory()->customer()->create())->get('/manage/walk-in')->assertForbidden();
    }

    public function test_staff_can_view_the_walk_in_grid(): void
    {
        Court::factory()->create();

        $this->actingAs(User::factory()->staff()->create())->get('/manage/walk-in')->assertOk();
    }

    public function test_walk_in_booking_can_use_the_current_hour_bypassing_the_online_min_notice(): void
    {
        $court = Court::factory()->create();
        $slotStart = $this->soonSlot();
        BusinessHour::updateOrCreate(
            ['day_of_week' => $slotStart->dayOfWeek],
            ['opens_at' => '00:00:00', 'closes_at' => '23:59:00', 'is_closed' => false],
        );

        $response = $this->actingAs(User::factory()->staff()->create())->post("/manage/walk-in/{$court->id}", [
            'date' => $slotStart->toDateString(),
            'start_time' => $slotStart->format('H:i:s'),
            'end_time' => $slotStart->addHour()->format('H:i:s'),
            'new_customer_name' => 'Juan Dela Cruz',
            'new_customer_email' => 'juan@example.com',
            'new_customer_phone' => '09171234567',
        ]);

        $booking = Booking::first();
        $response->assertRedirect(route('bookings.show', $booking));
        $this->assertSame(BookingSource::WalkIn, $booking->source);
        $this->assertDatabaseHas('users', ['email' => 'juan@example.com', 'role' => UserRole::Customer->value]);
        $this->assertSame($booking->user_id, User::where('email', 'juan@example.com')->first()->id);
    }

    public function test_walk_in_booking_can_use_an_existing_customer(): void
    {
        $court = Court::factory()->create();
        $existing = User::factory()->customer()->create(['name' => 'Maria Santos']);
        $slotStart = $this->soonSlot();
        BusinessHour::updateOrCreate(
            ['day_of_week' => $slotStart->dayOfWeek],
            ['opens_at' => '00:00:00', 'closes_at' => '23:59:00', 'is_closed' => false],
        );

        $response = $this->actingAs(User::factory()->staff()->create())->post("/manage/walk-in/{$court->id}", [
            'date' => $slotStart->toDateString(),
            'start_time' => $slotStart->format('H:i:s'),
            'end_time' => $slotStart->addHour()->format('H:i:s'),
            'existing_user_id' => $existing->id,
        ]);

        $booking = Booking::first();
        $response->assertRedirect(route('bookings.show', $booking));
        $this->assertSame($existing->id, $booking->user_id);
        $this->assertSame(1, User::where('name', 'Maria Santos')->count());
    }

    public function test_customer_search_returns_matching_customers(): void
    {
        $court = Court::factory()->create();
        $match = User::factory()->customer()->create(['name' => 'Pedro Reyes']);
        User::factory()->customer()->create(['name' => 'Someone Else']);

        $response = $this->actingAs(User::factory()->staff()->create())
            ->get("/manage/walk-in/{$court->id}?date=".now()->toDateString()."&start_time=09:00:00&end_time=10:00:00&q=Pedro");

        $response->assertOk()->assertSee('Pedro Reyes')->assertDontSee('Someone Else');
    }

    public function test_new_customer_email_must_be_unique(): void
    {
        $court = Court::factory()->create();
        $existing = User::factory()->customer()->create(['email' => 'taken@example.com']);
        $slotStart = $this->soonSlot();
        BusinessHour::updateOrCreate(
            ['day_of_week' => $slotStart->dayOfWeek],
            ['opens_at' => '00:00:00', 'closes_at' => '23:59:00', 'is_closed' => false],
        );

        $response = $this->actingAs(User::factory()->staff()->create())->post("/manage/walk-in/{$court->id}", [
            'date' => $slotStart->toDateString(),
            'start_time' => $slotStart->format('H:i:s'),
            'end_time' => $slotStart->addHour()->format('H:i:s'),
            'new_customer_name' => 'Someone New',
            'new_customer_email' => 'taken@example.com',
        ]);

        $response->assertSessionHasErrors('new_customer_email');
        $this->assertSame(0, Booking::count());
    }
}
