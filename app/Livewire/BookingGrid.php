<?php

namespace App\Livewire;

use App\Enums\SlotStatus;
use App\Exceptions\BookingUnavailableException;
use App\Models\Court;
use App\Models\Setting;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\PricingService;
use Illuminate\Support\Carbon;
use Livewire\Component;

/**
 * DEC-004: lets a customer select any combination of available slots -
 * different courts, non-consecutive times, doesn't matter - and confirm
 * them all as one batch via BookingService::bookMany(). Date navigation on
 * the surrounding page is still a plain link (full page reload); this
 * component only owns the grid + selection + review step for one date.
 */
class BookingGrid extends Component
{
    public string $date;

    /** @var array<string, array{court_id: int, court_name: string, date: string, start_time: string, end_time: string}> */
    public array $selected = [];

    public bool $reviewing = false;

    public ?string $notes = null;

    public ?string $error = null;

    public function mount(string $date): void
    {
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
        $this->error = null;

        $courts = Court::whereIn('id', collect($this->selected)->pluck('court_id'))->get()->keyBy('id');

        $slots = collect($this->selected)->map(fn (array $s) => [
            'court' => $courts->get($s['court_id']),
            'date' => $s['date'],
            'start_time' => $s['start_time'],
            'end_time' => $s['end_time'],
        ])->all();

        try {
            $bookings = $bookingService->bookMany(auth()->user(), $slots, notes: $this->notes);
        } catch (BookingUnavailableException $e) {
            $this->error = $e->getMessage();

            return;
        }

        $refs = collect($bookings)->map(fn ($b) => 'PB-'.$b->id)->join(', ');
        session()->flash('status', count($bookings).' booking'.(count($bookings) === 1 ? '' : 's')." confirmed: {$refs}");

        $this->redirect(route('bookings.mine'));
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
