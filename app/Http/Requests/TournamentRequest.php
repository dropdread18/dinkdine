<?php

namespace App\Http\Requests;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\OpenPlaySession;
use App\Models\Tournament;
use App\Models\TrainingSession;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class TournamentRequest extends FormRequest
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
            'tournament_name' => ['required', 'string', 'max:255'],
            'session_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i:s'],
            'end_time' => ['required', 'date_format:H:i:s', 'after:start_time'],
            'notes' => ['nullable', 'string', 'max:255'],
            'reclub_link' => ['nullable', 'url', 'max:500'],
        ];
    }

    /**
     * Same reasoning as TrainingSessionRequest/OpenPlaySessionRequest:
     * reject overlaps here rather than silently making an existing
     * booking invisible on the grid, or having two special-session types
     * both claim the same slot (only one status can ever display per
     * cell, so an unchecked overlap would just hide one of the two rows
     * a staff member created).
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
            $courtName = Court::find($courtId)?->name ?? "court #{$courtId}";

            $bookingConflicts = Booking::query()
                ->where('court_id', $courtId)
                ->whereDate('booking_date', $date)
                ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
                ->get()
                ->filter(fn (Booking $booking) => $start < $booking->end_time && $booking->start_time < $end);

            if ($bookingConflicts->isNotEmpty()) {
                $validator->errors()->add(
                    'court_id',
                    $bookingConflicts->count() === 1
                        ? "{$courtName}: this window conflicts with 1 existing booking. Cancel or reschedule it first."
                        : "{$courtName}: this window conflicts with {$bookingConflicts->count()} existing bookings. Cancel or reschedule them first."
                );

                return;
            }

            $openPlayConflicts = OpenPlaySession::query()
                ->where('court_id', $courtId)
                ->whereDate('session_date', $date)
                ->get()
                ->filter(fn (OpenPlaySession $session) => $start < $session->end_time && $session->start_time < $end);

            if ($openPlayConflicts->isNotEmpty()) {
                $validator->errors()->add('court_id', "{$courtName}: this window overlaps an Open Play session already scheduled there.");

                return;
            }

            $trainingConflicts = TrainingSession::query()
                ->where('court_id', $courtId)
                ->whereDate('session_date', $date)
                ->get()
                ->filter(fn (TrainingSession $session) => $start < $session->end_time && $session->start_time < $end);

            if ($trainingConflicts->isNotEmpty()) {
                $validator->errors()->add('court_id', "{$courtName}: this window overlaps a Training Session already scheduled there.");

                return;
            }

            $tournamentConflicts = Tournament::query()
                ->where('court_id', $courtId)
                ->whereDate('session_date', $date)
                ->when($this->route('tournament'), fn ($query, $tournament) => $query->whereKeyNot($tournament))
                ->get()
                ->filter(fn (Tournament $tournament) => $start < $tournament->end_time && $tournament->start_time < $end);

            if ($tournamentConflicts->isNotEmpty()) {
                $validator->errors()->add('court_id', "{$courtName}: this window overlaps another Tournament already scheduled there.");
            }
        });
    }
}
