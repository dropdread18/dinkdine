<?php

namespace App\Http\Controllers\Staff;

use App\Enums\BookingSource;
use App\Enums\UserRole;
use App\Exceptions\BookingUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\WalkInBookingRequest;
use App\Models\Court;
use App\Models\Setting;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WalkInBookingController extends Controller
{
    public function index(Request $request, AvailabilityService $availability): View
    {
        $date = $request->query('date', now()->toDateString());
        $maxAdvanceDays = (int) (Setting::get('max_advance_booking_days') ?? 30);

        return view('staff.walkin.index', [
            'date' => $date,
            'availability' => $availability->forDate($date),
            // Staff aren't held to the online min-notice rule - a walk-in
            // customer is standing at the counter wanting the current slot.
            'bookableFrom' => now(),
            'minDate' => now()->toDateString(),
            'maxDate' => now()->addDays($maxAdvanceDays)->toDateString(),
        ]);
    }

    public function create(Request $request, Court $court): View
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i:s'],
            'end_time' => ['required', 'date_format:H:i:s'],
        ]);

        $existingCustomers = collect();
        $q = $request->query('q');

        if ($q) {
            $existingCustomers = User::where('role', UserRole::Customer)
                ->where(function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                })
                ->limit(10)
                ->get();
        }

        return view('staff.walkin.create', [
            'court' => $court,
            'date' => $data['date'],
            'startTime' => $data['start_time'],
            'endTime' => $data['end_time'],
            'q' => $q ?? '',
            'existingCustomers' => $existingCustomers,
        ]);
    }

    public function store(WalkInBookingRequest $request, Court $court, BookingService $bookingService): RedirectResponse
    {
        $data = $request->validated();

        if (! empty($data['existing_user_id'])) {
            $customer = User::findOrFail($data['existing_user_id']);
        } else {
            $customer = User::create([
                'name' => $data['new_customer_name'],
                'email' => $data['new_customer_email'],
                'phone' => $data['new_customer_phone'] ?? null,
                // Raw string, not Hash::make() - the 'hashed' cast on User::password
                // hashes it on save; hashing here too would double-hash it.
                'password' => Str::random(32),
                'role' => UserRole::Customer,
            ]);
        }

        try {
            $booking = $bookingService->book(
                user: $customer,
                court: $court,
                date: $data['date'],
                startTime: $data['start_time'],
                endTime: $data['end_time'],
                notes: $data['notes'] ?? null,
                source: BookingSource::WalkIn,
                enforceBookingWindow: false,
            );
        } catch (BookingUnavailableException $e) {
            return back()->withErrors(['booking' => $e->getMessage()])->withInput();
        }

        return redirect()->route('bookings.show', $booking)->with('status', 'Walk-in booking created.');
    }
}
