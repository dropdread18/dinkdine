<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i:s'],
            'end_time' => ['required', 'date_format:H:i:s', 'after:start_time'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'existing_user_id' => ['nullable', 'exists:users,id'],
            'new_customer_name' => ['required_without:existing_user_id', 'nullable', 'string', 'max:255'],
            'new_customer_email' => ['required_without:existing_user_id', 'nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'new_customer_phone' => ['nullable', 'string', 'max:30'],
        ];
    }
}
