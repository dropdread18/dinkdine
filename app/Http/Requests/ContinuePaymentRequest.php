<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContinuePaymentRequest extends FormRequest
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
            'reference_number' => ['required', 'string', 'max:255'],
            'payment_proof' => ['nullable', 'image', 'max:8192'],
        ];
    }
}
