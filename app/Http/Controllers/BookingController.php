<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingUnavailableException;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Setting;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request, AvailabilityService $availability): View
    {
        $date = $request->query('date', now()->toDateString());
        $minNoticeMinutes = (int) (Setting::get('min_booking_notice_minutes') ?? 30);
        $maxAdvanceDays = (int) (Setting::get('max_advance_booking_days') ?? 30);

        return view('bookings.index', [
            'date' => $date,
            'availability' => $availability->forDate($date),
            'bookableFrom' => now()->addMinutes($minNoticeMinutes),
            'minDate' => now()->toDateString(),
            'maxDate' => now()->addDays($maxAdvanceDays)->toDateString(),
        ]);
    }

    public function create(StoreBookingRequest $request, Court $court): View
    {
        $data = $request->safe()->only(['date', 'start_time', 'end_time']);

        return view('bookings.create', [
            'court' => $court,
            'date' => $data['date'],
            'startTime' => $data['start_time'],
            'endTime' => $data['end_time'],
        ]);
    }

    public function store(StoreBookingRequest $request, Court $court, BookingService $bookingService): RedirectResponse
    {
        $data = $request->validated();

        try {
            $booking = $bookingService->book(
                user: $request->user(),
                court: $court,
                date: $data['date'],
                startTime: $data['start_time'],
                endTime: $data['end_time'],
                notes: $data['notes'] ?? null,
            );
        } catch (BookingUnavailableException $e) {
            return back()->withErrors(['booking' => $e->getMessage()]);
        }

        return redirect()->route('bookings.show', $booking)->with('status', 'Booking confirmed.');
    }

    public function show(Booking $booking): View
    {
        Gate::authorize('view', $booking);

        return view('bookings.show', ['booking' => $booking->load(['court', 'user'])]);
    }

    public function mine(Request $request): View
    {
        $bookings = $request->user()->bookings()->with('court')->orderByDesc('booking_date')->orderByDesc('start_time')->get();

        $today = Carbon::today()->toDateString();

        return view('bookings.mine', [
            'upcoming' => $bookings->filter(fn (Booking $b) => $b->booking_date->toDateString() >= $today)->sortBy('booking_date'),
            'past' => $bookings->filter(fn (Booking $b) => $b->booking_date->toDateString() < $today),
        ]);
    }
}
