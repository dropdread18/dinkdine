<?php

namespace Tests\Feature\Services;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BusinessHour;
use App\Models\ClosurePeriod;
use App\Models\Court;
use App\Models\Payment;
use App\Services\ReportingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportingServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReportingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ReportingService;
    }

    public function test_revenue_sums_only_paid_payments_within_the_date_range(): void
    {
        $inRange = Booking::factory()->create();
        Payment::factory()->create([
            'booking_id' => $inRange->id, 'status' => PaymentStatus::Paid, 'amount' => 300, 'paid_at' => '2026-06-15 10:00:00',
        ]);

        $unpaid = Booking::factory()->create();
        Payment::factory()->create(['booking_id' => $unpaid->id, 'status' => PaymentStatus::Unpaid, 'amount' => 400]);

        $outOfRange = Booking::factory()->create();
        Payment::factory()->create([
            'booking_id' => $outOfRange->id, 'status' => PaymentStatus::Paid, 'amount' => 500, 'paid_at' => '2026-07-01 10:00:00',
        ]);

        $result = $this->service->revenue(Carbon::parse('2026-06-01'), Carbon::parse('2026-06-30'));

        $this->assertEquals(300.0, $result['total']);
        $this->assertSame(1, $result['count']);
    }

    public function test_booking_counts_groups_by_status_within_the_date_range(): void
    {
        Booking::factory()->create(['booking_date' => '2026-06-10']);
        Booking::factory()->cancelled()->create(['booking_date' => '2026-06-11']);
        Booking::factory()->cancelled()->create(['booking_date' => '2026-06-12']);
        Booking::factory()->create(['booking_date' => '2026-07-01']); // out of range

        $counts = $this->service->bookingCounts(Carbon::parse('2026-06-01'), Carbon::parse('2026-06-30'));

        $this->assertSame(3, $counts['total']);
        $this->assertSame(1, $counts['confirmed']);
        $this->assertSame(2, $counts['cancelled']);
        $this->assertSame(0, $counts['completed']);
    }

    public function test_court_utilization_calculates_percentage_from_business_hours_and_bookings(): void
    {
        // A single Monday, 08:00-18:00 = 10 possible hours.
        BusinessHour::create(['day_of_week' => 1, 'opens_at' => '08:00:00', 'closes_at' => '18:00:00', 'is_closed' => false]);
        $monday = Carbon::parse('2026-06-01'); // known Monday
        $this->assertSame(1, $monday->dayOfWeek);

        $court = Court::factory()->create();
        Booking::factory()->create([
            'court_id' => $court->id, 'booking_date' => $monday->toDateString(), 'start_time' => '09:00:00', 'end_time' => '10:00:00',
        ]);
        Booking::factory()->create([
            'court_id' => $court->id, 'booking_date' => $monday->toDateString(), 'start_time' => '10:00:00', 'end_time' => '11:00:00',
        ]);

        $result = $this->service->courtUtilization($monday, $monday)->firstWhere('court.id', $court->id);

        $this->assertEquals(10.0, $result['possible_hours']);
        $this->assertEquals(2.0, $result['booked_hours']);
        $this->assertEquals(20.0, $result['utilization_percent']);
    }

    public function test_court_utilization_excludes_cancelled_bookings(): void
    {
        BusinessHour::create(['day_of_week' => 1, 'opens_at' => '08:00:00', 'closes_at' => '18:00:00', 'is_closed' => false]);
        $monday = Carbon::parse('2026-06-01');

        $court = Court::factory()->create();
        Booking::factory()->cancelled()->create([
            'court_id' => $court->id, 'booking_date' => $monday->toDateString(), 'start_time' => '09:00:00', 'end_time' => '10:00:00',
        ]);

        $result = $this->service->courtUtilization($monday, $monday)->firstWhere('court.id', $court->id);

        $this->assertEquals(0.0, $result['booked_hours']);
    }

    public function test_court_utilization_treats_a_closure_period_day_as_zero_possible_hours(): void
    {
        BusinessHour::create(['day_of_week' => 1, 'opens_at' => '08:00:00', 'closes_at' => '18:00:00', 'is_closed' => false]);
        $monday = Carbon::parse('2026-06-01');

        ClosurePeriod::factory()->create([
            'starts_at' => $monday->copy()->startOfDay(),
            'ends_at' => $monday->copy()->endOfDay(),
        ]);

        Court::factory()->create();

        $result = $this->service->courtUtilization($monday, $monday)->first();

        $this->assertEquals(0.0, $result['possible_hours']);
        $this->assertNull($result['utilization_percent']);
    }

    public function test_court_utilization_percent_is_null_when_no_business_hours_exist(): void
    {
        Court::factory()->create();

        $result = $this->service->courtUtilization(Carbon::parse('2026-06-01'), Carbon::parse('2026-06-01'))->first();

        $this->assertEquals(0.0, $result['possible_hours']);
        $this->assertNull($result['utilization_percent']);
    }
}
