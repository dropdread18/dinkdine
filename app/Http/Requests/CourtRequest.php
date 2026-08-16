<?php

namespace App\Http\Requests;

use App\Enums\CourtStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class CourtRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'court_number' => ['required', 'integer', 'min:1', Rule::unique('courts', 'court_number')->ignore($this->route('court'))],
            'hourly_rate' => ['required', 'numeric', 'min:0'],
            'evening_hourly_rate' => ['required', 'numeric', 'min:0'],
            'status' => ['required', new Enum(CourtStatus::class)],
            'court_type' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
