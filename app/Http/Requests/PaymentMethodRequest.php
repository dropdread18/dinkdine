<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentMethodRequest extends FormRequest
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
        // A payment method without a QR code isn't one a customer can pay
        // with, so the image is required on create - but not on update
        // (no route-bound model yet), where "didn't re-upload one" just
        // means "keep the existing QR".
        $qrCodeRule = $this->route('payment_method') ? 'nullable' : 'required';

        return [
            'name' => ['required', 'string', 'max:255'],
            'qr_code' => [$qrCodeRule, 'image', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
