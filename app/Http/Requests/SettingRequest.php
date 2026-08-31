<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
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
            'facility_name' => ['required', 'string', 'max:255'],
            'facility_address' => ['nullable', 'string', 'max:255'],
            'facility_phone' => ['nullable', 'string', 'max:255'],
            'facility_email' => ['nullable', 'email', 'max:255'],
            'facility_facebook' => ['nullable', 'url', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
            'default_booking_duration_minutes' => ['required', 'integer', 'min:15'],
            'max_booking_duration_minutes' => ['required', 'integer', 'min:15', 'gte:default_booking_duration_minutes'],
            'min_booking_notice_minutes' => ['required', 'integer', 'min:0'],
            'max_advance_booking_days' => ['required', 'integer', 'min:1'],
            'cancellation_deadline_hours' => ['required', 'integer', 'min:0'],
            'max_simultaneous_bookings_per_customer' => ['required', 'integer', 'min:1'],
            'default_court_hourly_rate' => ['required', 'numeric', 'min:0'],
            'payment_hold_minutes' => ['required', 'integer', 'min:1', 'max:120'],
            'payment_instructions' => ['nullable', 'string', 'max:2000'],
            'open_play_link' => ['nullable', 'url', 'max:255'],
            'facility_logo' => ['nullable', 'image', 'max:8192'],
            'remove_facility_logo' => ['nullable', 'boolean'],
        ];
    }
}
