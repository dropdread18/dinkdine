<?php

namespace App\Services;

use App\Models\Court;
use Carbon\CarbonImmutable;

/**
 * Requirements.md §28, §69. Weekday/weekend and member pricing rules are
 * explicitly future work (§28) and not implemented here. Time-of-day
 * pricing (day vs evening) IS implemented, per owner request: 6:00 AM to
 * 5:00 PM at Court::$hourly_rate, 5:00 PM onwards at
 * Court::$evening_hourly_rate.
 */
class PricingService
{
    private const string EVENING_STARTS_AT = '17:00:00';

    public function calculate(Court $court, string $startTime, string $endTime): float
    {
        $start = CarbonImmutable::createFromFormat('H:i:s', $startTime);
        $end = CarbonImmutable::createFromFormat('H:i:s', $endTime);

        // Guard against reversed args, same as before this method grew a
        // second rate - never use diffInMinutes() here, Carbon 3 flips its
        // sign depending on argument order (found the hard way once
        // already, see BookingServiceTest).
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        $eveningStart = CarbonImmutable::createFromFormat('H:i:s', self::EVENING_STARTS_AT);

        // A slot can straddle 5:00 PM (e.g. a 90-minute slot starting at
        // 4:30 PM) - split it into its day and evening portions and price
        // each at its own rate, rather than pricing the whole slot off of
        // whichever rate the start time happens to fall under.
        $dayEnd = $end->lt($eveningStart) ? $end : $eveningStart;
        $dayMinutes = max(0, ($dayEnd->getTimestamp() - $start->getTimestamp()) / 60);

        $eveningBegin = $start->gt($eveningStart) ? $start : $eveningStart;
        $eveningMinutes = max(0, ($end->getTimestamp() - $eveningBegin->getTimestamp()) / 60);

        $dayPrice = (float) $court->hourly_rate * ($dayMinutes / 60);
        $eveningPrice = (float) $court->evening_hourly_rate * ($eveningMinutes / 60);

        return round($dayPrice + $eveningPrice, 2);
    }
}
