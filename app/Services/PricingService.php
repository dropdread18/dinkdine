<?php

namespace App\Services;

use App\Models\Court;
use Carbon\CarbonImmutable;

/**
 * Requirements.md §28, §69. Weekday/weekend and member pricing rules are
 * explicitly future work (§28) and not implemented here. Time-of-day
 * pricing (day vs evening) IS implemented, per owner request: 6:00 AM to
 * 5:00 PM at Court::$hourly_rate, 5:00 PM to 6:00 AM (i.e. everything
 * outside the day window, including past midnight) at
 * Court::$evening_hourly_rate.
 */
class PricingService
{
    private const string DAY_STARTS_AT = '06:00:00';

    private const string DAY_ENDS_AT = '17:00:00';

    public function calculate(Court $court, string $startTime, string $endTime): float
    {
        // '23:59:59' is AvailabilityService's internal marker for "this
        // slot runs to midnight" (it can't use '00:00:00' there without
        // breaking same-day conflict checks - see its generateTimeSlots()).
        // For pricing, treat it as the true midnight boundary it represents
        // rather than charging for one second short of a full hour - a
        // customer booking the last slot of the day shouldn't see an odd
        // "299.92" instead of a round rate.
        $endsAtMidnight = in_array('23:59:59', [$startTime, $endTime], true);

        $start = CarbonImmutable::createFromFormat('H:i:s', $startTime);
        $end = CarbonImmutable::createFromFormat('H:i:s', $endTime);

        // Guard against reversed args, same as before this method grew a
        // second rate - never use diffInMinutes() here, Carbon 3 flips its
        // sign depending on argument order (found the hard way once
        // already, see BookingServiceTest).
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        if ($endsAtMidnight) {
            $end = $end->addSecond();
        }

        $dayStart = CarbonImmutable::createFromFormat('H:i:s', self::DAY_STARTS_AT);
        $dayEnd = CarbonImmutable::createFromFormat('H:i:s', self::DAY_ENDS_AT);

        // A slot can straddle either boundary (e.g. 4:30 AM-7:30 AM crosses
        // 6:00 AM, 4:30 PM-5:30 PM crosses 5:00 PM) - price only the portion
        // that actually overlaps the 6 AM-5 PM day window at the day rate,
        // and everything else (including anything before 6 AM) at the
        // evening rate, rather than assuming the day rate covers everything
        // up to 5 PM.
        $overlapStart = $start->gt($dayStart) ? $start : $dayStart;
        $overlapEnd = $end->lt($dayEnd) ? $end : $dayEnd;
        $dayMinutes = max(0, ($overlapEnd->getTimestamp() - $overlapStart->getTimestamp()) / 60);

        $totalMinutes = ($end->getTimestamp() - $start->getTimestamp()) / 60;
        $eveningMinutes = $totalMinutes - $dayMinutes;

        $dayPrice = (float) $court->hourly_rate * ($dayMinutes / 60);
        $eveningPrice = (float) $court->evening_hourly_rate * ($eveningMinutes / 60);

        return round($dayPrice + $eveningPrice, 2);
    }
}
