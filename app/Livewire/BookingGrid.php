<?php

namespace App\Livewire;

use App\Enums\SlotStatus;
use App\Enums\UserRole;
use App\Exceptions\BookingUnavailableException;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Setting;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\PricingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * DEC-004: lets a customer select any combination of available slots -
 * different courts, non-consecutive times, doesn't matter - and confirm
 * them all as one batch via BookingService::bookMany(). Date navigation on
 * the surrounding page is still a plain link (full page reload); this
 * component only owns the grid + selection + review step for one date.
 *
 * DEC-003: also open to guests (not logged in). A guest supplies name/
 * email/phone at review time instead of a password; we find-or-create a
 * Customer account behind the scenes - same pattern as staff-created
 * walk-in customers - then log them into it before booking, so every
 * existing customer feature (My Bookings, cancel/reschedule) just works
 * afterward with zero new authorization code.
 *
 * Feedback session (post-launch), later revised (owner feedback: payment
 * must be collected before ANY online booking confirms, not just guest
 * ones): every online confirmation - guest or already-logged-in customer
 * alike - holds the slot(s) as Pending for a configurable window (Setting
 * 'payment_hold_minutes', default 10)
 * (BookingService::bookMany(..., requiresPaymentHold: true)) while the
 * customer pays via the facility's own arrangement (Setting
 * 'payment_instructions'/QR code) and reports back a reference number
 * and/or a screenshot of the receipt (submitPaymentReference() ->
 * BookingService::confirmWithReference()). If they don't, the hold
 * silently expires via the bookings:expire-payment-holds scheduled
 * command, freeing the slot. Walk-in bookings created by staff at the
 * counter are unaffected - those go through WalkInBookingController, not
 * this component, since staff collect payment in person there.
 */
class BookingGrid extends Component
{
    use WithFileUploads;

    public string $date;

    /** @var array<string, array{court_id: int, court_name: string, date: string, start_time: string, end_time: string}> */
    public array $selected = [];

    public bool $reviewing = false;

    public bool $awaitingPayment = false;

    /**
     * Mobile view shows one court at a time (a wide multi-court grid
     * doesn't fit a phone screen) via a row of tabs. Null until the
     * customer picks one; the view falls back to the first court in
     * the day's availability so there's always something selected.
     */
    public ?int $mobileCourt = null;

    /** @var int[] */
    public array $pendingBookingIds = [];

    public ?string $holdExpiresAt = null;

    public ?string $paymentReference = null;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $paymentProof = null;

    public ?string $notes = null;

    public ?string $error = null;

    public ?string $guestName = null;

    public ?string $guestEmail = null;

    public ?string $guestPhone = null;

    public function mount(string $date): void
    {
        $this->assertNotStaffOrAdmin();

        $this->date = $date;
    }

    public function toggleSlot(int $courtId, string $courtName, string $startTime, string $endTime): void
    {
        if ($this->awaitingPayment) {
            return;
        }

        $this->error = null;
        $key = "{$courtId}|{$startTime}";

        if (isset($this->selected[$key])) {
            unset($this->selected[$key]);

            return;
        }

        $this->selected[$key] = [
            'court_id' => $courtId,
            'court_name' => $courtName,
            'date' => $this->date,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
    }

    public function selectMobileCourt(int $courtId): void
    {
        $this->mobileCourt = $courtId;
    }

    public function removeSlot(string $key): void
    {
        unset($this->selected[$key]);

        if (empty($this->selected)) {
            $this->reviewing = false;
        }
    }

    public function startReview(): void
    {
        if (empty($this->selected) || $this->awaitingPayment) {
            return;
        }

        $this->error = null;
        $this->reviewing = true;
    }

    public function backToGrid(): void
    {
        if ($this->awaitingPayment) {
            return;
        }

        $this->reviewing = false;
        $this->error = null;
    }

    public function confirmBookings(BookingService $bookingService): void
    {
        $this->assertNotStaffOrAdmin();
        $this->error = null;

        try {
            $customer = $this->resolveCustomer();
        } catch (ValidationException $e) {
            $this->error = collect($e->errors())->flatten()->first();

            return;
        }

        $courts = Court::whereIn('id', collect($this->selected)->pluck('court_id'))->get()->keyBy('id');

        $slots = collect($this->selected)->map(fn (array $s) => [
            'court' => $courts->get($s['court_id']),
            'date' => $s['date'],
            'start_time' => $s['start_time'],
            'end_time' => $s['end_time'],
        ])->all();

        // Every online booking - guest or already-logged-in customer - now
        // holds as Pending until payment is reported, not just guest
        // checkouts. Owner feedback: a known customer account is still not
        // proof that payment actually happened.
        try {
            $bookings = $bookingService->bookMany($customer, $slots, notes: $this->notes, requiresPaymentHold: true);
        } catch (BookingUnavailableException $e) {
            $this->error = $e->getMessage();

            return;
        }

        if (! Auth::check()) {
            Auth::login($customer);
        }

        $this->pendingBookingIds = collect($bookings)->pluck('id')->all();
        $this->holdExpiresAt = $bookings[0]->hold_expires_at->toIso8601String();
        $this->reviewing = false;
        $this->awaitingPayment = true;
    }

    public function submitPaymentReference(BookingService $bookingService): void
    {
        $this->error = null;

        // Owner request: the reference number is now required outright
        // (previously "at least one of reference/screenshot"). The
        // screenshot upload stays optional, alongside the reference.
        $this->validate([
            'paymentReference' => ['required', 'string', 'max:255'],
            'paymentProof' => ['nullable', 'image', 'max:8192'],
        ]);

        // Private disk, deliberately - a payment screenshot is the
        // customer's own financial record. The 'public' disk is served with
        // zero auth checks by the webserver; this stays behind
        // BookingController::paymentProof()'s ownership/staff check instead.
        $proofPath = $this->paymentProof?->store('payment-proofs', 'local');

        $bookings = Booking::whereIn('id', $this->pendingBookingIds)->get()->all();

        try {
            $confirmed = $bookingService->confirmWithReference($bookings, $this->paymentReference, $proofPath);
        } catch (BookingUnavailableException $e) {
            // Any failure here (expired hold, already resolved, etc) means
            // this hold is no longer valid one way or another - back to a
            // clean grid rather than leaving them stuck on a dead payment
            // screen for a booking that no longer exists in this state.
            $this->awaitingPayment = false;
            $this->selected = [];
            $this->pendingBookingIds = [];
            $this->holdExpiresAt = null;
            $this->error = $e->getMessage();

            return;
        }

        session()->flash('confirmed_booking_ids', collect($confirmed)->pluck('id')->all());

        $this->redirect(route('bookings.confirmation'));
    }

    /**
     * @throws ValidationException
     */
    private function resolveCustomer(): User
    {
        if (Auth::check()) {
            return Auth::user();
        }

        $this->validate([
            'guestName' => ['required', 'string', 'max:255'],
            'guestEmail' => ['required', 'email', 'max:255'],
            'guestPhone' => ['required', 'string', 'max:30'],
        ]);

        $existing = User::where('email', $this->guestEmail)->first();

        if ($existing && ! $existing->isCustomer()) {
            throw ValidationException::withMessages([
                'guestEmail' => 'This email is already associated with a staff/admin account. Please log in instead.',
            ]);
        }

        if ($existing) {
            return $existing;
        }

        return User::create([
            'name' => $this->guestName,
            'email' => $this->guestEmail,
            'phone' => $this->guestPhone,
            // Raw string, not Hash::make() - the 'hashed' cast on User::password
            // hashes it on save; hashing here too would double-hash it.
            'password' => Str::random(32),
            'role' => UserRole::Customer,
        ]);
    }

    private function assertNotStaffOrAdmin(): void
    {
        $user = Auth::user();

        if ($user && ! $user->isCustomer()) {
            abort(403);
        }
    }

    public function render(AvailabilityService $availabilityService, PricingService $pricingService)
    {
        $minNoticeMinutes = (int) (Setting::get('min_booking_notice_minutes') ?? 30);
        $bookableFrom = now()->addMinutes($minNoticeMinutes);

        $slotPrices = collect($this->selected)->map(
            fn (array $s) => $pricingService->calculate(Court::find($s['court_id']), $s['start_time'], $s['end_time'])
        );

        $pendingBookings = $this->awaitingPayment
            ? Booking::with('court')->whereIn('id', $this->pendingBookingIds)->get()
            : collect();

        return view('livewire.booking-grid', [
            'availability' => $availabilityService->forDate($this->date),
            'bookableFrom' => $bookableFrom,
            'slotPrices' => $slotPrices,
            'totalPrice' => $slotPrices->sum(),
            'slotStatus' => SlotStatus::class,
            'paymentInstructions' => Setting::get('payment_instructions'),
            'paymentQrCode' => Setting::get('payment_qr_code'),
            'pendingBookings' => $pendingBookings,
            'paymentHoldMinutes' => (int) (Setting::get('payment_hold_minutes') ?? 10),
        ]);
    }
}
