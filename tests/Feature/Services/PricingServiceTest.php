<?php

namespace Tests\Feature\Services;

use App\Models\Court;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculates_price_for_a_one_hour_slot(): void
    {
        $court = Court::factory()->create(['hourly_rate' => 300]);

        $price = (new PricingService)->calculate($court, '09:00:00', '10:00:00');

        $this->assertEquals(300.00, $price);
    }

    public function test_calculates_price_for_a_partial_hour_slot(): void
    {
        $court = Court::factory()->create(['hourly_rate' => 300]);

        $price = (new PricingService)->calculate($court, '09:00:00', '09:30:00');

        $this->assertEquals(150.00, $price);
    }

    public function test_price_is_positive_regardless_of_argument_order(): void
    {
        // Guards against the Carbon 3 diffInMinutes sign-flip bug found
        // earlier when this logic lived in BookingService.
        $court = Court::factory()->create(['hourly_rate' => 300]);

        $price = (new PricingService)->calculate($court, '10:00:00', '09:00:00');

        $this->assertEquals(300.00, $price);
    }

    public function test_a_slot_entirely_before_5pm_uses_the_day_rate(): void
    {
        $court = Court::factory()->create(['hourly_rate' => 250, 'evening_hourly_rate' => 350]);

        $price = (new PricingService)->calculate($court, '09:00:00', '10:00:00');

        $this->assertEquals(250.00, $price);
    }

    public function test_a_slot_entirely_at_or_after_5pm_uses_the_evening_rate(): void
    {
        $court = Court::factory()->create(['hourly_rate' => 250, 'evening_hourly_rate' => 350]);

        $price = (new PricingService)->calculate($court, '18:00:00', '19:00:00');

        $this->assertEquals(350.00, $price);
    }

    public function test_a_slot_starting_exactly_at_5pm_uses_the_evening_rate(): void
    {
        $court = Court::factory()->create(['hourly_rate' => 250, 'evening_hourly_rate' => 350]);

        $price = (new PricingService)->calculate($court, '17:00:00', '18:00:00');

        $this->assertEquals(350.00, $price);
    }

    public function test_a_slot_straddling_5pm_is_split_proportionally_across_both_rates(): void
    {
        $court = Court::factory()->create(['hourly_rate' => 250, 'evening_hourly_rate' => 350]);

        // 4:30 PM-5:30 PM: 30 minutes of day rate + 30 minutes of evening rate.
        $price = (new PricingService)->calculate($court, '16:30:00', '17:30:00');

        $this->assertEquals(125.00 + 175.00, $price);
    }

    public function test_a_slot_before_6am_uses_the_evening_rate(): void
    {
        $court = Court::factory()->create(['hourly_rate' => 250, 'evening_hourly_rate' => 300]);

        $price = (new PricingService)->calculate($court, '00:00:00', '05:00:00');

        $this->assertEquals(1500.00, $price);
    }

    public function test_a_slot_starting_exactly_at_6am_uses_the_day_rate(): void
    {
        $court = Court::factory()->create(['hourly_rate' => 250, 'evening_hourly_rate' => 300]);

        $price = (new PricingService)->calculate($court, '06:00:00', '07:00:00');

        $this->assertEquals(250.00, $price);
    }

    public function test_a_slot_straddling_6am_is_split_proportionally_across_both_rates(): void
    {
        $court = Court::factory()->create(['hourly_rate' => 250, 'evening_hourly_rate' => 300]);

        // 5:30 AM-6:30 AM: 30 minutes of evening rate + 30 minutes of day rate.
        $price = (new PricingService)->calculate($court, '05:30:00', '06:30:00');

        $this->assertEquals(150.00 + 125.00, $price);
    }

    public function test_a_slot_spanning_midnight_to_past_5am_uses_the_evening_rate_throughout(): void
    {
        $court = Court::factory()->create(['hourly_rate' => 250, 'evening_hourly_rate' => 300]);

        // Mirrors the reported bug: 12 AM-5 PM was entirely priced at the
        // day rate because there was no 6 AM boundary at all - only the
        // 6 AM-5 PM slice should ever get the day rate.
        $price = (new PricingService)->calculate($court, '00:00:00', '17:00:00');

        $this->assertEquals((6 * 300) + (11 * 250), $price);
    }

    public function test_the_last_slot_of_the_day_is_charged_the_full_evening_rate(): void
    {
        $court = Court::factory()->create(['hourly_rate' => 250, 'evening_hourly_rate' => 300]);

        // '23:59:59' is AvailabilityService's own marker for "runs to
        // midnight" (a real hour would collide with tomorrow's 00:00:00 in
        // same-day time comparisons) - a customer booking 11 PM-midnight
        // should still see a round 300, not 299.92 for the missing second.
        $price = (new PricingService)->calculate($court, '23:00:00', '23:59:59');

        $this->assertEquals(300.00, $price);
    }

    public function test_a_multi_slot_booking_ending_at_the_day_boundary_is_charged_in_full(): void
    {
        $court = Court::factory()->create(['hourly_rate' => 250, 'evening_hourly_rate' => 300]);

        // Two merged slots: 10 PM-11 PM and 11 PM-midnight, i.e. 2 full
        // hours - not 1h59m59s.
        $price = (new PricingService)->calculate($court, '22:00:00', '23:59:59');

        $this->assertEquals(600.00, $price);
    }
}
