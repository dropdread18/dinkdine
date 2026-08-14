<?php

namespace App\Http\Controllers\Staff;

use App\Enums\CourtStatus;
use App\Exceptions\BookingUnavailableException;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Court;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

/**
 * Requirements.md §12/§7: staff view today's bookings, check customers in,
 * mark completed/no-show, and see/update live court status.
 */
class CheckInController extends Controller
{
    public function index(): View
    {
        return view('staff.checkin.index', [
            'bookings' => Booking::query()
                ->with(['court', 'user'])
                ->whereDate('booking_date', now())
                ->orderBy('start_time')
                ->get(),
            'courts' => Court::orderBy('sort_order')->orderBy('court_number')->get(),
        ]);
    }

    public function checkIn(Booking $booking, BookingService $bookingService): RedirectResponse
    {
        try {
            $bookingService->checkIn($booking);
        } catch (BookingUnavailableException $e) {
            return back()->withErrors(['booking' => $e->getMessage()]);
        }

        return back()->with('status', "{$booking->user->name} checked in.");
    }

    public function markCompleted(Booking $booking, BookingService $bookingService): RedirectResponse
    {
        try {
            $bookingService->markCompleted($booking);
        } catch (BookingUnavailableException $e) {
            return back()->withErrors(['booking' => $e->getMessage()]);
        }

        return back()->with('status', 'Booking marked completed.');
    }

    public function markNoShow(Booking $booking, BookingService $bookingService): RedirectResponse
    {
        try {
            $bookingService->markNoShow($booking);
        } catch (BookingUnavailableException $e) {
            return back()->withErrors(['booking' => $e->getMessage()]);
        }

        return back()->with('status', 'Booking marked no-show.');
    }

    public function updateCourtStatus(Request $request, Court $court): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', new Enum(CourtStatus::class)],
        ]);

        $court->update(['status' => $data['status']]);

        return back()->with('status', "{$court->name} status updated.");
    }
}
