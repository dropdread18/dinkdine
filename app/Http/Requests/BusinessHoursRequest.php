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

                    continue;
                }

                if ($row['closes_at'] <= $row['opens_at']) {
                    $validator->errors()->add("hours.{$day}.closes_at", 'Closing time must be after opening time.');
                }
            }
        });
    }
}
