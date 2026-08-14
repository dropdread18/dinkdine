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
}
