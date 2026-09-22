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
use App\Services\PricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    /**
     * Shows the picked slots back (any combination of courts/times, from
     * the checkbox grid on index) plus the customer search/create form -
     * the same role create() used to play for a single slot, now for
     * however many were checked. `slots` arrives as a plain array of JSON
     * strings (see index.blade.php) rather than nested bracket-indexed
     * fields - the grid doesn't know ahead of render time how many boxes
     * will end up checked, so each checkbox carries its own complete
     * slot as one self-contained value instead of relying on a
     * pre-assigned index.
     */
    public function review(Request $request, PricingService $pricing): View|RedirectResponse
    {
        try {
            $slots = $this->decodeSlots($request);
        } catch (BookingUnavailableException $e) {
            return redirect()->route('manage.walkin.index')->withErrors(['booking' => $e->getMessage()]);
        }

        $slotPrices = collect($slots)->map(
            fn (array $slot) => $pricing->calculate($slot['court'], $slot['start_time'], $slot['end_time'])
        );

        return view('staff.walkin.review', [
            'slots' => $slots,
            'slotPrices' => $slotPrices,
            'totalPrice' => $slotPrices->sum(),
            'rawSlots' => $request->query('slots', []),
        ]);
    }

    public function store(WalkInBookingRequest $request, BookingService $bookingService): RedirectResponse
    {
        $data = $request->validated();

        try {
            $slots = $this->decodeSlots($request);
        } catch (BookingUnavailableException $e) {
            return back()->withErrors(['booking' => $e->getMessage()])->withInput();
        }

        // Customer creation and bookMany() share one transaction (bookMany()
        // opens its own nested one via a SAVEPOINT, same pattern already
        // established for BookingService's own nested transactions) so a
        // conflict on any slot rolls back a brand-new customer account too,
        // not just the bookings - a multi-slot submission is one real-world
        // transaction for the person at the counter, and a partial failure
        // shouldn't leave a customer record behind with nothing booked.
        try {
            [$customer, $bookings] = DB::transaction(function () use ($data, $slots, $bookingService) {
                // Walk-in intake only ever asks for a name (owner feedback:
                // needs to be simple enough to teach a non-technical staff
                // member on the spot) - no email/phone, and no lookup
                // against existing customers. Every walk-in submission
                // creates its own new customer record, so email still needs
                // some unique value to satisfy the column - this one is
                // never shown to or used by the customer, it's a walk-in
                // account with no online login.
                $customer = User::create([
                    'name' => $data['customer_name'],
                    'email' => Str::uuid().'@walkin.local',
                    'password' => Str::random(32),
                    'role' => UserRole::Customer,
                ]);

                $bookings = $bookingService->bookMany(
                    user: $customer,
                    slots: $slots,
                    notes: $data['notes'] ?? null,
                    source: BookingSource::WalkIn,
                    enforceBookingWindow: false,
                );

                return [$customer, $bookings];
            });
        } catch (BookingUnavailableException $e) {
            return back()->withErrors(['booking' => $e->getMessage()])->withInput();
        }

        $count = count($bookings);
        $status = "{$count} walk-in booking".($count === 1 ? '' : 's')." created for {$customer->name}.";

        return count($bookings) === 1
            ? redirect()->route('bookings.show', $bookings[0])->with('status', $status)
            : redirect()->route('manage.walkin.index', ['date' => $bookings[0]->booking_date->toDateString()])->with('status', $status);
    }

    /**
     * Throws the same exception type a booking conflict does
     * (BookingUnavailableException) rather than aborting outright - both
     * callers (review() for display, store() before it) already need to
     * turn "something's wrong with the slots" into a normal redirect/error
     * response, not a raw HTTP error page, and store() already has a
     * BookingUnavailableException catch block for the real conflict case
     * that this can reuse instead of adding a second, differently-shaped
     * failure path next to it.
     *
     * @return array<int, array{court: Court, date: string, start_time: string, end_time: string}>
     *
     * @throws BookingUnavailableException
     */
    private function decodeSlots(Request $request): array
    {
        $raw = $request->input('slots', []);

        if (empty($raw)) {
            throw new BookingUnavailableException('Select at least one time slot.');
        }

        $courts = Court::query()->get()->keyBy('id');

        return collect($raw)->map(function (string $json) use ($courts) {
            $decoded = json_decode($json, true);

            if (! is_array($decoded) || ! isset($decoded['court_id'], $decoded['date'], $decoded['start_time'], $decoded['end_time'])) {
                throw new BookingUnavailableException('One of the selected slots was invalid. Please select your slots again.');
            }

            $court = $courts->get($decoded['court_id']);

            if (! $court) {
                throw new BookingUnavailableException('One of the selected courts no longer exists. Please select your slots again.');
            }

            return [
                'court' => $court,
                'date' => $decoded['date'],
                'start_time' => $decoded['start_time'],
                'end_time' => $decoded['end_time'],
            ];
        })->all();
    }
}
