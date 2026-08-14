<?php

namespace App\Livewire;

use App\Enums\SlotStatus;
use App\Enums\UserRole;
use App\Exceptions\BookingUnavailableException;
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
 */
class BookingGrid extends Component
{
    public string $date;

    /** @var array<string, array{court_id: int, court_name: string, date: string, start_time: string, end_time: string}> */
    public array $selected = [];

    public bool $reviewing = false;

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

    public function removeSlot(string $key): void
    {
        unset($this->selected[$key]);

        if (empty($this->selected)) {
            $this->reviewing = false;
        }
    }

    public function startReview(): void
    {
        if (empty($this->selected)) {
            return;
        }

        $this->error = null;
        $this->reviewing = true;
    }

    public function backToGrid(): void
    {
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

        try {
            $bookings = $bookingService->bookMany($customer, $slots, notes: $this->notes);
        } catch (BookingUnavailableException $e) {
            $this->error = $e->getMessage();

            return;
        }

        if (! Auth::check()) {
            Auth::login($customer);
        }

        $refs = collect($bookings)->map(fn ($b) => 'PB-'.$b->id)->join(', ');
        session()->flash('status', count($bookings).' booking'.(count($bookings) === 1 ? '' : 's')." confirmed: {$refs}");

        $this->redirect(route('bookings.mine'));
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

        $totalPrice = collect($this->selected)->sum(
            fn (array $s) => $pricingService->calculate(Court::find($s['court_id']), $s['start_time'], $s['end_time'])
        );

        return view('livewire.booking-grid', [
            'availability' => $availabilityService->forDate($this->date),
            'bookableFrom' => $bookableFrom,
            'totalPrice' => $totalPrice,
            'slotStatus' => SlotStatus::class,
        ]);
    }
}
