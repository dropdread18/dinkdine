<?php

namespace App\Services;

use App\Models\Court;
use Carbon\CarbonImmutable;

/**
 * Requirements.md §28, §69. Basic hourly-rate pricing only - weekday/
 * weekend, peak-hour, and member pricing rules are explicitly future
 * work (§28) and not implemented here.
 */
class PricingService
{
    public function calculate(Court $court, string $startTime, string $endTime): float
    {
        $start = CarbonImmutable::createFromFormat('H:i:s', $startTime);
        $end = CarbonImmutable::createFromFormat('H:i:s', $endTime);
        $minutes = abs($end->getTimestamp() - $start->getTimestamp()) / 60;

        return round((float) $court->hourly_rate * ($minutes / 60), 2);
    }
}
