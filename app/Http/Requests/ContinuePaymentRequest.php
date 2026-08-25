<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
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
            'reference_number' => ['nullable', 'string', 'max:255'],
            'payment_proof' => ['nullable', 'image', 'max:8192'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('reference_number') && ! $this->hasFile('payment_proof')) {
                $validator->errors()->add('reference_number', 'Enter your payment reference number or upload a screenshot of your receipt.');
            }
        });
    }
}
