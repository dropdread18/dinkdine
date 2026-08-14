<?php

namespace App\Http\Controllers\Staff;

use App\Exceptions\BookingUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Setting;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $query = Booking::query()->with(['court', 'user']);

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

        $bookings = $query->orderByDesc('booking_date')->orderByDesc('start_time')->paginate(20)->withQueryString();

        return view('staff.bookings.index', [
            'bookings' => $bookings,
            'courts' => Court::orderBy('sort_order')->orderBy('court_number')->get(),
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
