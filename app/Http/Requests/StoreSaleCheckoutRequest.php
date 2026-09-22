<?php

namespace App\Http\Requests;

use App\Enums\StoreSalePaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleCheckoutRequest extends FormRequest
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
            // quantities is a plain {product_id: quantity} map - every
            // product on the grid submits its own row's input under its
            // own id, whether zero or not (PosController::decodeItems()
            // drops the zeros). An empty cart is a normal business-rule
            // rejection (StoreSaleException), not a malformed-request
            // validation error, so no min:1 here.
            'quantities' => ['nullable', 'array'],
            'quantities.*' => ['nullable', 'integer', 'min:0'],
            'payment_method' => ['required', Rule::in(array_map(fn ($m) => $m->value, StoreSalePaymentMethod::cases()))],
            'amount_paid' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
