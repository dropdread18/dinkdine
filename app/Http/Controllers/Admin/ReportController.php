<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\ReportingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, ReportingService $reports): View
    {
        [$start, $end, $range] = $this->resolveRange($request);

        return view('admin.reports.index', [
            'start' => $start,
            'end' => $end,
            'range' => $range,
            'revenue' => $reports->revenue($start, $end),
            'bookingCounts' => $reports->bookingCounts($start, $end),
            'utilization' => $reports->courtUtilization($start, $end),
            'last7Days' => $this->last7DaysRevenue(),
        ]);
    }

    /**
     * Trailing 7 days ending today, independent of the range switcher above
     * it - the mockup labels this panel "Revenue — Last 7 Days" as a fixed
     * window, not "revenue over the selected range". Grouped in PHP rather
     * than a DATE()/strftime() grouped query - this codebase has hit real
     * SQLite-vs-MySQL date-handling mismatches before (see ReportingService
     * docblock), so date-string grouping stays off the SQL side.
     *
     * @return \Illuminate\Support\Collection<int, array{label: string, total: float}>
     */
    private function last7DaysRevenue(): \Illuminate\Support\Collection
    {
        $today = Carbon::today();

        $paidByDate = Payment::query()
            ->where('status', PaymentStatus::Paid)
            ->whereBetween('paid_at', [$today->copy()->subDays(6)->startOfDay(), $today->copy()->endOfDay()])
            ->get(['amount', 'paid_at'])
            ->groupBy(fn (Payment $payment) => $payment->paid_at->toDateString())
            ->map(fn ($payments) => (float) $payments->sum('amount'));

        return collect(range(6, 0))->map(fn (int $daysAgo) => [
            'label' => $today->copy()->subDays($daysAgo)->format('D'),
            'total' => $paidByDate->get($today->copy()->subDays($daysAgo)->toDateString(), 0.0),
        ]);
    }

    public function exportBookings(Request $request): StreamedResponse
    {
        [$start, $end] = $this->resolveRange($request);

        $bookings = Booking::query()
            ->with(['court', 'user'])
            ->whereDate('booking_date', '>=', $start->toDateString())
            ->whereDate('booking_date', '<=', $end->toDateString())
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->get();

        return response()->streamDownload(function () use ($bookings) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Booking #', 'Customer', 'Email', 'Court', 'Date', 'Start', 'End', 'Price', 'Status', 'Payment Status', 'Source']);

            foreach ($bookings as $booking) {
                fputcsv($handle, [
                    'PB-'.$booking->id,
                    $booking->user->name,
                    $booking->user->email,
                    $booking->court->name,
                    $booking->booking_date->toDateString(),
                    $booking->start_time,
                    $booking->end_time,
                    $booking->price,
                    $booking->status->label(),
                    $booking->payment_status->label(),
                    $booking->source->label(),
                ]);
            }

            fclose($handle);
        }, "bookings-{$start->toDateString()}-to-{$end->toDateString()}.csv", ['Content-Type' => 'text/csv']);
    }

    public function exportPayments(Request $request): StreamedResponse
    {
        [$start, $end] = $this->resolveRange($request);

        $payments = Payment::query()
            ->with(['booking.court', 'booking.user'])
            ->whereHas('booking', function ($query) use ($start, $end) {
                $query->whereDate('booking_date', '>=', $start->toDateString())
                    ->whereDate('booking_date', '<=', $end->toDateString());
            })
            ->orderBy('created_at')
            ->get();

        return response()->streamDownload(function () use ($payments) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Booking #', 'Customer', 'Amount', 'Status', 'Method', 'Paid At']);

            foreach ($payments as $payment) {
                fputcsv($handle, [
                    'PB-'.$payment->booking->id,
                    $payment->booking->user->name,
                    $payment->amount,
                    $payment->status->label(),
                    $payment->method,
                    $payment->paid_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, "payments-{$start->toDateString()}-to-{$end->toDateString()}.csv", ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function resolveRange(Request $request): array
    {
        $range = $request->query('range', 'today');
        $today = Carbon::today();

        return match ($range) {
            'week' => [$today->copy()->startOfWeek(), $today->copy()->endOfWeek(), 'week'],
            'month' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth(), 'month'],
            'custom' => [
                Carbon::parse($request->query('start', $today->toDateString())),
                Carbon::parse($request->query('end', $today->toDateString())),
                'custom',
            ],
            default => [$today->copy(), $today->copy(), 'today'],
        };
    }
}
