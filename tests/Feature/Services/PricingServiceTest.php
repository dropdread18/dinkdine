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
}
