<?php

namespace App\Http\Requests;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class CourtMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'court_id' => ['required', 'exists:courts,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Requirements.md §41/§45: the backend must reject overlaps, not just
     * the booking form. A maintenance window closes every slot it covers
     * (AvailabilityService::resolveSlot checks maintenance before bookings),
     * so scheduling one over an existing Pending/Confirmed booking would
     * silently make that booking invisible on the grid rather than blocking
     * anything - reject it here instead, same as BookingService rejects a
     * booking over another booking.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled(['court_id', 'starts_at', 'ends_at']) || $validator->errors()->isNotEmpty()) {
                return;
            }

            $starts = Carbon::parse($this->input('starts_at'));
            $ends = Carbon::parse($this->input('ends_at'));

            $conflicts = Booking::query()
                ->where('court_id', $this->input('court_id'))
                ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
                ->whereDate('booking_date', '>=', $starts->toDateString())
                ->whereDate('booking_date', '<=', $ends->toDateString())
                ->get()
                ->filter(function (Booking $booking) use ($starts, $ends) {
                    $bookingStart = $booking->booking_date->copy()->setTimeFromTimeString($booking->start_time);
                    $bookingEnd = $booking->booking_date->copy()->setTimeFromTimeString($booking->end_time);

                    return $starts->lt($bookingEnd) && $bookingStart->lt($ends);
                });

            if ($conflicts->isNotEmpty()) {
                $validator->errors()->add(
                    'starts_at',
                    $conflicts->count() === 1
                        ? 'This window conflicts with 1 existing booking. Cancel or reschedule it first.'
                        : "This window conflicts with {$conflicts->count()} existing bookings. Cancel or reschedule them first."
                );
            }
        });
    }
}
