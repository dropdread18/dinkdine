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
    public function index(Request $request): View
    {
        $bookings = Booking::query()
            ->with(['court', 'user', 'payment'])
            ->whereDate('booking_date', now())
            ->orderBy('start_time');

        // Same search shape as Admin\PaymentController::index() - booking #
        // (with or without the "PB-" prefix) or the customer's name/email/
        // phone. Stays a plain ->get(), not ->paginate(): a single day's
        // worth of bookings is small, and callers (including the tests)
        // already expect a Collection they can ->count() directly.
        if ($q = $request->query('q')) {
            $idCandidate = preg_replace('/^PB-/i', '', trim($q));

            $bookings->where(function ($sub) use ($q, $idCandidate) {
                $sub->where('id', $idCandidate)
                    ->orWhereHas('user', function ($userQuery) use ($q) {
                        $userQuery->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%")
                            ->orWhere('phone', 'like', "%{$q}%");
                    });
            });
        }

        return view('staff.checkin.index', [
            'bookings' => $bookings->get(),
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
