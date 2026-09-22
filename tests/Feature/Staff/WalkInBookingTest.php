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

    /**
     * @return array<int, string>
     */
    private function slotPayload(Court $court, string $date, string $startTime, string $endTime): array
    {
        return [json_encode([
            'court_id' => $court->id,
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ])];
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

    public function test_staff_sees_the_customer_name_on_an_already_booked_slot(): void
    {
        $court = Court::factory()->create();
        $booking = Booking::factory()->create(['court_id' => $court->id, 'booking_date' => now()->toDateString()]);

        $response = $this->actingAs(User::factory()->staff()->create())->get('/manage/walk-in?date='.now()->toDateString());

        $response->assertOk()->assertSee($booking->user->name);
    }

    public function test_walk_in_booking_can_use_the_current_hour_bypassing_the_online_min_notice(): void
    {
        $court = Court::factory()->create();
        $slotStart = $this->soonSlot();
        BusinessHour::updateOrCreate(
            ['day_of_week' => $slotStart->dayOfWeek],
            ['opens_at' => '00:00:00', 'closes_at' => '23:59:00', 'is_closed' => false],
        );

        $response = $this->actingAs(User::factory()->staff()->create())->post('/manage/walk-in', [
            'slots' => $this->slotPayload($court, $slotStart->toDateString(), $slotStart->format('H:i:s'), $slotStart->addHour()->format('H:i:s')),
            'customer_name' => 'Juan Dela Cruz',
        ]);

        $booking = Booking::first();
        $response->assertRedirect(route('bookings.show', $booking));
        $this->assertSame(BookingSource::WalkIn, $booking->source);
        $this->assertDatabaseHas('users', ['name' => 'Juan Dela Cruz', 'role' => UserRole::Customer->value]);
        $this->assertSame($booking->user_id, User::where('name', 'Juan Dela Cruz')->first()->id);
    }

    public function test_walk_in_booking_can_select_multiple_time_slots_on_the_same_court(): void
    {
        $court = Court::factory()->create();
        $slotStart = $this->soonSlot();
        BusinessHour::updateOrCreate(
            ['day_of_week' => $slotStart->dayOfWeek],
            ['opens_at' => '00:00:00', 'closes_at' => '23:59:00', 'is_closed' => false],
        );

        $response = $this->actingAs(User::factory()->staff()->create())->post('/manage/walk-in', [
            'slots' => [
                json_encode(['court_id' => $court->id, 'date' => $slotStart->toDateString(), 'start_time' => $slotStart->format('H:i:s'), 'end_time' => $slotStart->copy()->addHour()->format('H:i:s')]),
                json_encode(['court_id' => $court->id, 'date' => $slotStart->toDateString(), 'start_time' => $slotStart->copy()->addHour()->format('H:i:s'), 'end_time' => $slotStart->copy()->addHours(2)->format('H:i:s')]),
            ],
            'customer_name' => 'Juan Dela Cruz',
        ]);

        $customer = User::where('name', 'Juan Dela Cruz')->firstOrFail();
        $response->assertRedirect(route('manage.walkin.index', ['date' => $slotStart->toDateString()]));
        $this->assertSame(2, Booking::where('user_id', $customer->id)->count());
    }

    public function test_walk_in_booking_can_select_multiple_courts_at_once(): void
    {
        $courtA = Court::factory()->create();
        $courtB = Court::factory()->create();
        $slotStart = $this->soonSlot();
        BusinessHour::updateOrCreate(
            ['day_of_week' => $slotStart->dayOfWeek],
            ['opens_at' => '00:00:00', 'closes_at' => '23:59:00', 'is_closed' => false],
        );
        $endTime = $slotStart->copy()->addHour()->format('H:i:s');

        $response = $this->actingAs(User::factory()->staff()->create())->post('/manage/walk-in', [
            'slots' => [
                json_encode(['court_id' => $courtA->id, 'date' => $slotStart->toDateString(), 'start_time' => $slotStart->format('H:i:s'), 'end_time' => $endTime]),
                json_encode(['court_id' => $courtB->id, 'date' => $slotStart->toDateString(), 'start_time' => $slotStart->format('H:i:s'), 'end_time' => $endTime]),
            ],
            'customer_name' => 'Doubles Group',
        ]);

        $customer = User::where('name', 'Doubles Group')->firstOrFail();
        $response->assertRedirect();
        $this->assertSame(2, Booking::where('user_id', $customer->id)->count());
        $this->assertSame(1, Booking::where(['user_id' => $customer->id, 'court_id' => $courtA->id])->count());
        $this->assertSame(1, Booking::where(['user_id' => $customer->id, 'court_id' => $courtB->id])->count());
    }

    public function test_a_conflict_on_one_slot_rolls_back_the_whole_multi_slot_walk_in(): void
    {
        $court = Court::factory()->create();
        $slotStart = $this->soonSlot();
        BusinessHour::updateOrCreate(
            ['day_of_week' => $slotStart->dayOfWeek],
            ['opens_at' => '00:00:00', 'closes_at' => '23:59:00', 'is_closed' => false],
        );
        $secondSlotStart = $slotStart->copy()->addHour();
        Booking::factory()->create([
            'court_id' => $court->id,
            'booking_date' => $secondSlotStart->toDateString(),
            'start_time' => $secondSlotStart->format('H:i:s'),
            'end_time' => $secondSlotStart->copy()->addHour()->format('H:i:s'),
        ]);

        $response = $this->actingAs(User::factory()->staff()->create())->post('/manage/walk-in', [
            'slots' => [
                json_encode(['court_id' => $court->id, 'date' => $slotStart->toDateString(), 'start_time' => $slotStart->format('H:i:s'), 'end_time' => $secondSlotStart->format('H:i:s')]),
                json_encode(['court_id' => $court->id, 'date' => $secondSlotStart->toDateString(), 'start_time' => $secondSlotStart->format('H:i:s'), 'end_time' => $secondSlotStart->copy()->addHour()->format('H:i:s')]),
            ],
            'customer_name' => 'Juan Dela Cruz',
        ]);

        $response->assertSessionHasErrors('booking');
        $this->assertDatabaseMissing('users', ['name' => 'Juan Dela Cruz']);
        $this->assertSame(1, Booking::count());
    }

    public function test_review_page_shows_the_selected_slots_and_combined_total(): void
    {
        $court = Court::factory()->create(['hourly_rate' => 250]);
        $slotStart = $this->soonSlot();

        $response = $this->actingAs(User::factory()->staff()->create())->get('/manage/walk-in/review?'.http_build_query([
            'slots' => [
                json_encode(['court_id' => $court->id, 'date' => $slotStart->toDateString(), 'start_time' => $slotStart->format('H:i:s'), 'end_time' => $slotStart->copy()->addHour()->format('H:i:s')]),
                json_encode(['court_id' => $court->id, 'date' => $slotStart->toDateString(), 'start_time' => $slotStart->copy()->addHour()->format('H:i:s'), 'end_time' => $slotStart->copy()->addHours(2)->format('H:i:s')]),
            ],
        ]));

        $response->assertOk()->assertSee('2 slots selected')->assertSee($court->name);
    }

    public function test_review_page_only_asks_for_the_customer_name(): void
    {
        $court = Court::factory()->create();

        $response = $this->actingAs(User::factory()->staff()->create())->get('/manage/walk-in/review?'.http_build_query([
            'slots' => $this->slotPayload($court, now()->toDateString(), '09:00:00', '10:00:00'),
        ]));

        $response->assertOk()
            ->assertSee('Customer name')
            ->assertDontSee('Email')
            ->assertDontSee('Phone');
    }

    public function test_customer_name_is_required(): void
    {
        $court = Court::factory()->create();
        $slotStart = $this->soonSlot();
        BusinessHour::updateOrCreate(
            ['day_of_week' => $slotStart->dayOfWeek],
            ['opens_at' => '00:00:00', 'closes_at' => '23:59:00', 'is_closed' => false],
        );

        $response = $this->actingAs(User::factory()->staff()->create())->post('/manage/walk-in', [
            'slots' => $this->slotPayload($court, $slotStart->toDateString(), $slotStart->format('H:i:s'), $slotStart->addHour()->format('H:i:s')),
        ]);

        $response->assertSessionHasErrors('customer_name');
        $this->assertSame(0, Booking::count());
    }

    public function test_submitting_with_no_slots_selected_is_rejected(): void
    {
        $response = $this->actingAs(User::factory()->staff()->create())->post('/manage/walk-in', [
            'slots' => [],
            'customer_name' => 'Juan Dela Cruz',
        ]);

        $response->assertSessionHasErrors('booking');
        $this->assertSame(0, Booking::count());
        $this->assertDatabaseMissing('users', ['name' => 'Juan Dela Cruz']);
    }
}
