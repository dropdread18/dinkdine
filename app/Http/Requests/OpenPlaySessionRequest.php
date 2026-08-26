<?php

namespace App\Http\Requests;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Court;
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
        $rules = [
            'session_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i:s'],
            'end_time' => ['required', 'date_format:H:i:s', 'after:start_time'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];

        // Creating schedules one Open Play session per selected court (the
        // owner runs Open Play on both courts at once) - editing still
        // targets the single court a session already belongs to, since
        // there's no sensible way to "split" an existing row across courts.
        if ($this->isMethod('post')) {
            $rules['court_ids'] = ['required', 'array', 'min:1'];
            $rules['court_ids.*'] = ['exists:courts,id'];
        } else {
            $rules['court_id'] = ['required', 'exists:courts,id'];
        }

        return $rules;
    }

    /**
     * Same reasoning as CourtMaintenanceRequest: reject overlaps here
     * rather than silently making an existing booking invisible on the
     * grid, or blocking a slot two Open Play sessions both claim. Checked
     * per court, since a batch create can mix a free court with a
     * conflicting one.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled(['session_date', 'start_time', 'end_time']) || $validator->errors()->isNotEmpty()) {
                return;
            }

            $date = $this->input('session_date');
            $start = $this->input('start_time');
            $end = $this->input('end_time');
            $courtField = $this->isMethod('post') ? 'court_ids' : 'court_id';
            $courtIds = $this->isMethod('post') ? $this->input('court_ids', []) : [$this->input('court_id')];

            foreach ($courtIds as $courtId) {
                $bookingConflicts = Booking::query()
                    ->where('court_id', $courtId)
                    ->whereDate('booking_date', $date)
                    ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
                    ->get()
                    ->filter(fn (Booking $booking) => $start < $booking->end_time && $booking->start_time < $end);

                if ($bookingConflicts->isNotEmpty()) {
                    $courtName = Court::find($courtId)?->name ?? "court #{$courtId}";
                    $validator->errors()->add(
                        $courtField,
                        $bookingConflicts->count() === 1
                            ? "{$courtName}: this window conflicts with 1 existing booking. Cancel or reschedule it first."
                            : "{$courtName}: this window conflicts with {$bookingConflicts->count()} existing bookings. Cancel or reschedule them first."
                    );

                    continue;
                }

                $sessionConflicts = OpenPlaySession::query()
                    ->where('court_id', $courtId)
                    ->whereDate('session_date', $date)
                    ->when($this->route('session'), fn ($query, $session) => $query->whereKeyNot($session))
                    ->get()
                    ->filter(fn (OpenPlaySession $session) => $start < $session->end_time && $session->start_time < $end);

                if ($sessionConflicts->isNotEmpty()) {
                    $courtName = Court::find($courtId)?->name ?? "court #{$courtId}";
                    $validator->errors()->add($courtField, "{$courtName}: this window overlaps another Open Play session already scheduled there.");
                }
            }
        });
    }
}
