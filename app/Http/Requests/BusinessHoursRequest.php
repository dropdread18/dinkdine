<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class BusinessHoursRequest extends FormRequest
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
            'hours' => ['required', 'array', 'size:7'],
            'hours.*.is_closed' => ['nullable', 'boolean'],
            'hours.*.opens_at' => ['nullable', 'date_format:H:i:s'],
            'hours.*.closes_at' => ['nullable', 'date_format:H:i:s'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ($this->input('hours', []) as $day => $row) {
                if (! empty($row['is_closed'])) {
                    continue;
                }

                if (empty($row['opens_at']) || empty($row['closes_at'])) {
                    $validator->errors()->add("hours.{$day}.opens_at", 'Opening and closing time are required for an open day.');
                }

                // A closing time at or before the opening time is not an
                // error - it means the day runs past midnight (e.g. opens
                // 06:00, closes 02:00 the next morning). AvailabilityService
                // splits that into today's slots plus tomorrow's spillover.
            }
        });
    }
}
