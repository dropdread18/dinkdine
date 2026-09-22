<?php

namespace App\Http\Controllers\Staff;

use App\Exceptions\BookingUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Setting;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $query = Booking::query()->with(['court', 'user', 'payment']);

        if ($date = $request->query('date')) {
            $query->whereDate('booking_date', $date);
        }

        if ($courtId = $request->query('court_id')) {
            $query->where('court_id', $courtId);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($paymentStatus = $request->query('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($source = $request->query('source')) {
            $query->where('source', $source);
        }

        if ($q = $request->query('q')) {
            $idCandidate = preg_replace('/^PB-/i', '', trim($q));

            $query->where(function ($sub) use ($q, $idCandidate) {
                $sub->where('id', $idCandidate)
                    ->orWhereHas('user', function ($userQuery) use ($q) {
                        $userQuery->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%")
                            ->orWhere('phone', 'like', "%{$q}%");
                    });
            });
        }

        // Owner feedback: expired guest-checkout holds (abandoned before a
        // reference number was ever submitted - see ExpirePaymentHolds)
        // aren't a real booking anyone needs to see day to day and were
        // just cluttering this list. Hidden unless staff explicitly ask
        // for them via the Status filter, so they're still reachable, just
        // not in-the-way by default.
        if ($status !== \App\Enums\BookingStatus::Expired->value) {
            $query->where('status', '!=', \App\Enums\BookingStatus::Expired);
        }

        $sort = $request->query('sort', 'date_desc');
        match ($sort) {
            'date_asc' => $query->orderBy('booking_date')->orderBy('start_time'),
            'customer' => $query->orderBy(User::select('name')->whereColumn('users.id', 'bookings.user_id')),
            'status' => $query->orderBy('status')->orderByDesc('booking_date'),
            default => $query->orderByDesc('booking_date')->orderByDesc('start_time'),
        };

        $bookings = $query->paginate(20)->withQueryString();

        // Same grouping as the Payments list (PaymentController::index()) -
        // every booking confirmed together in one checkout shares its
        // Payment's reference_number, so a 2-hour booking that's really 2
        // separate Booking/Payment rows reads as one item instead of
        // cluttering the list as two unrelated-looking rows. A booking
        // whose payment has no reference number yet (still unpaid, or a
        // walk-in that was never charged online) just gets its own group.
        $groups = $bookings->getCollection()->groupBy(
            fn (Booking $booking) => $booking->payment?->reference_number
                ? $booking->user_id.'|'.$booking->payment->reference_number
                : 'single-'.$booking->id
        );

        return view('staff.bookings.index', [
            'bookings' => $bookings,
            'groups' => $groups,
            'courts' => Court::orderBy('sort_order')->orderBy('court_number')->get(),
            'sort' => $sort,
        ]);
    }

    public function cancel(Request $request, Booking $booking, BookingService $bookingService): RedirectResponse
    {
        try {
            $bookingService->cancel($booking, $request->input('reason'));
        } catch (BookingUnavailableException $e) {
            return back()->withErrors(['booking' => $e->getMessage()]);
        }

        return back()->with('status', 'Booking cancelled.');
    }

    public function reschedule(Request $request, Booking $booking, AvailabilityService $availability): View
    {
        $date = $request->query('date', $booking->booking_date->toDateString());
        $maxAdvanceDays = (int) (Setting::get('max_advance_booking_days') ?? 30);

        return view('staff.bookings.reschedule', [
            'booking' => $booking,
            'date' => $date,
            'availability' => $availability->forDate($date, excludeBookingId: $booking->id),
            'bookableFrom' => now(),
            'minDate' => now()->toDateString(),
            'maxDate' => now()->addDays($maxAdvanceDays)->toDateString(),
        ]);
    }

    public function rescheduleForm(StoreBookingRequest $request, Booking $booking, Court $court): View
    {
        $data = $request->safe()->only(['date', 'start_time', 'end_time']);

        return view('staff.bookings.reschedule-confirm', [
            'booking' => $booking,
            'court' => $court,
            'date' => $data['date'],
            'startTime' => $data['start_time'],
            'endTime' => $data['end_time'],
        ]);
    }

    public function rescheduleUpdate(StoreBookingRequest $request, Booking $booking, Court $court, BookingService $bookingService): RedirectResponse
    {
        $data = $request->validated();

        try {
            $bookingService->reschedule($booking, $court, $data['date'], $data['start_time'], $data['end_time']);
        } catch (BookingUnavailableException $e) {
            return back()->withErrors(['booking' => $e->getMessage()]);
        }

        return redirect()->route('bookings.show', $booking)->with('status', 'Booking rescheduled.');
    }
}
