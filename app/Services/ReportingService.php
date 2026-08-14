<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BusinessHour;
use App\Models\ClosurePeriod;
use App\Models\Court;
use App\Models\Payment;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Requirements.md §35. Read-only aggregate queries - no writes, so no
 * transactions/notifications to worry about here, unlike the rest of the
 * service layer.
 *
 * Known simplifications, documented rather than silently assumed:
 * - Revenue is based on Payment.paid_at (when money was actually recorded
 *   as received), not the booking's play date - a booking paid today for
 *   a session next week counts as today's revenue, which matches how
 *   "Today's Revenue" reads on a facility dashboard.
 * - Court utilization's denominator (possible open hours) subtracts
 *   facility-wide closures but NOT per-court maintenance windows - a
 *   court under maintenance for part of the range will show lower
 *   utilization than it "should", since its possible-hours isn't reduced
 *   to match. Acceptable approximation for a small single-facility app;
 *   revisit if maintenance scheduling UI is ever built and this matters.
 */
class ReportingService
{
    /**
     * @return array{total: float, count: int}
     */
    public function revenue(CarbonInterface $start, CarbonInterface $end): array
    {
        $query = Payment::query()
            ->where('status', PaymentStatus::Paid)
            ->whereBetween('paid_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()]);

        return [
            'total' => (float) $query->sum('amount'),
            'count' => $query->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function bookingCounts(CarbonInterface $start, CarbonInterface $end): array
    {
        $counts = $this->whereDateRange(Booking::query(), 'booking_date', $start, $end)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $result = ['total' => 0];

        foreach (BookingStatus::cases() as $status) {
            $count = (int) ($counts[$status->value] ?? 0);
            $result[$status->value] = $count;
            $result['total'] += $count;
        }

        return $result;
    }

    /**
     * @return Collection<int, array{court: Court, booked_hours: float, possible_hours: float, utilization_percent: ?float}>
     */
    public function courtUtilization(CarbonInterface $start, CarbonInterface $end): Collection
    {
        $possibleHours = $this->possibleOpenHours($start, $end);

        $bookedHoursByCourtId = $this->whereDateRange(Booking::query(), 'booking_date', $start, $end)
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])
            ->get()
            ->groupBy('court_id')
            ->map(fn (Collection $bookings) => $bookings->sum(
                fn (Booking $b) => $this->hoursBetween($b->start_time, $b->end_time)
            ));

        return Court::orderBy('sort_order')->orderBy('court_number')->get()
            ->map(function (Court $court) use ($bookedHoursByCourtId, $possibleHours) {
                $bookedHours = round((float) $bookedHoursByCourtId->get($court->id, 0.0), 1);

                return [
                    'court' => $court,
                    'booked_hours' => $bookedHours,
                    'possible_hours' => round($possibleHours, 1),
                    'utilization_percent' => $possibleHours > 0 ? round(($bookedHours / $possibleHours) * 100, 1) : null,
                ];
            });
    }

    private function possibleOpenHours(CarbonInterface $start, CarbonInterface $end): float
    {
        $businessHoursByDay = BusinessHour::all()->keyBy('day_of_week');

        $closures = ClosurePeriod::query()
            ->where('starts_at', '<=', $end->copy()->endOfDay())
            ->where('ends_at', '>=', $start->copy()->startOfDay())
            ->get();

        $total = 0.0;

        foreach (CarbonPeriod::create($start, $end) as $date) {
            $businessHour = $businessHoursByDay->get($date->dayOfWeek);

            if (! $businessHour || $businessHour->is_closed) {
                continue;
            }

            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();

            $closedThatDay = $closures->contains(
                fn (ClosurePeriod $closure) => $closure->starts_at->lte($dayEnd) && $closure->ends_at->gte($dayStart)
            );

            if ($closedThatDay) {
                continue;
            }

            $total += $this->hoursBetween($businessHour->opens_at, $businessHour->closes_at);
        }

        return $total;
    }

    /**
     * Timestamp subtraction + abs(), not diffInHours/diffInMinutes - Carbon
     * 3's default sign/direction bit this exact pattern once already in
     * PricingService, so the safe form is used everywhere duration math
     * happens in this codebase.
     */
    private function hoursBetween(string $startTime, string $endTime): float
    {
        $start = Carbon::createFromFormat('H:i:s', $startTime);
        $end = Carbon::createFromFormat('H:i:s', $endTime);

        return abs($end->getTimestamp() - $start->getTimestamp()) / 3600;
    }

    /**
     * whereDate() with operators, not whereBetween() with two same-format
     * date strings. MySQL's native DATE type has no time component, so
     * whereBetween('col', ['2026-06-01', '2026-06-01']) works there - but
     * SQLite (used in tests) stores a 'date'-cast column with a full
     * '2026-06-01 00:00:00' value, which sorts AFTER the plain '2026-06-01'
     * upper bound as a string and gets silently excluded from a plain
     * whereBetween. whereDate() extracts just the date part at the SQL
     * level (DATE()/strftime() depending on driver) so it's correct
     * regardless of whether the stored value carries a time component.
     * Found via a real test failure (10 possible hours computed correctly,
     * 0 booked hours when 2 were expected) - not something to intuit from
     * reading the schema up front.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Booking>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Booking>
     */
    private function whereDateRange($query, string $column, CarbonInterface $start, CarbonInterface $end)
    {
        return $query
            ->whereDate($column, '>=', $start->toDateString())
            ->whereDate($column, '<=', $end->toDateString());
    }
}
