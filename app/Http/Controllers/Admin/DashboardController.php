<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Payment;
use App\Services\ReportingService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(ReportingService $reportingService): View
    {
        $today = Carbon::today();
        $nowTime = now()->format('H:i:s');

        $revenue = $reportingService->revenue($today, $today);
        $bookingCounts = $reportingService->bookingCounts($today, $today);
        $utilization = $reportingService->courtUtilization($today, $today);

        // "Occupied right now", not "booked at all today" - a court whose
        // only booking today already finished, or hasn't started yet,
        // isn't occupied at this exact moment. Pending + Confirmed matches
        // BookingStatus::blocksAvailability()'s own definition of "still
        // occupies the court", reused here rather than inventing a
        // different status set for this one card.
        $occupiedCourtCount = Booking::query()
            ->whereDate('booking_date', $today)
            ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
            ->where('start_time', '<=', $nowTime)
            ->where('end_time', '>', $nowTime)
            ->distinct('court_id')
            ->count('court_id');

        $upcomingBookings = Booking::query()
            ->with(['court', 'user'])
            ->whereDate('booking_date', $today)
            ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
            ->where('end_time', '>', $nowTime)
            ->orderBy('start_time')
            ->limit(6)
            ->get();

        // Owner feedback: the dashboard only ever showed a bare count of
        // pending payments, with no way to act on it without navigating
        // away to search for them. This surfaces who's actually waiting
        // and links straight to the booking detail page's Mark Paid action.
        $pendingPayments = Payment::query()
            ->with(['booking.court', 'booking.user'])
            ->where('status', PaymentStatus::Pending)
            ->orderBy('created_at')
            ->limit(6)
            ->get();

        return view('dashboard.admin', [
            'todayRevenue' => $revenue['total'],
            'todayBookingsCount' => $bookingCounts['total'],
            'occupiedCourtCount' => $occupiedCourtCount,
            'totalCourtCount' => Court::count(),
            'pendingPaymentsCount' => Payment::where('status', PaymentStatus::Pending)->count(),
            'pendingPayments' => $pendingPayments,
            'upcomingBookings' => $upcomingBookings,
            'utilization' => $utilization,
        ]);
    }
}
