<?php

namespace App\Http\Requests;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\OpenPlaySession;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class OpenPlaySessionRequest extends FormRequest
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
            'session_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i:s'],
            'end_time' => ['required', 'date_format:H:i:s', 'after:start_time'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Same reasoning as CourtMaintenanceRequest: reject overlaps here
     * rather than silently making an existing booking invisible on the
     * grid, or blocking a slot two Open Play sessions both claim.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled(['court_id', 'session_date', 'start_time', 'end_time']) || $validator->errors()->isNotEmpty()) {
                return;
            }

            $courtId = $this->input('court_id');
            $date = $this->input('session_date');
            $start = $this->input('start_time');
            $end = $this->input('end_time');

            $bookingConflicts = Booking::query()
                ->where('court_id', $courtId)
                ->whereDate('booking_date', $date)
                ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
                ->get()
                ->filter(fn (Booking $booking) => $start < $booking->end_time && $booking->start_time < $end);

            if ($bookingConflicts->isNotEmpty()) {
                $validator->errors()->add(
                    'start_time',
                    $bookingConflicts->count() === 1
                        ? 'This window conflicts with 1 existing booking. Cancel or reschedule it first.'
                        : "This window conflicts with {$bookingConflicts->count()} existing bookings. Cancel or reschedule them first."
                );

                return;
            }

            $sessionConflicts = OpenPlaySession::query()
                ->where('court_id', $courtId)
                ->whereDate('session_date', $date)
                ->when($this->route('session'), fn ($query, $session) => $query->whereKeyNot($session))
                ->get()
                ->filter(fn (OpenPlaySession $session) => $start < $session->end_time && $session->start_time < $end);

            if ($sessionConflicts->isNotEmpty()) {
                $validator->errors()->add('start_time', 'This window overlaps another Open Play session already scheduled on this court.');
            }
        });
    }
}
