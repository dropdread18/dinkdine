<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WalkInBookingRequest extends FormRequest
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
            // Deliberately no rule here for the actual date/start_time/
            // end_time of each slot - they travel inside slots[]'s
            // JSON-encoded entries (WalkInBookingController::decodeSlots()),
            // not as top-level fields, since a walk-in submission can now
            // carry any number of them. Also deliberately no min:1/required
            // here - an empty selection is a normal business-rule rejection
            // (BookingUnavailableException, same "booking" error key/message
            // as an actual conflict), not a malformed-request validation
            // error, so it's decodeSlots() that raises it, not this rule.
            'slots' => ['nullable', 'array'],
            'slots.*' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'customer_name' => ['required', 'string', 'max:255'],
        ];
    }
}
