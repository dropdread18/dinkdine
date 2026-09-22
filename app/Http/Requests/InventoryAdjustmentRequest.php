<?php

namespace App\Http\Requests;

use App\Enums\InventoryMovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryAdjustmentRequest extends FormRequest
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
            // Only the subset of InventoryMovementType meant for a manual
            // adjustment - Stock In has its own form, Sale/Returned aren't
            // user-initiated yet (see InventoryMovementType::adjustmentTypes()).
            'type' => ['required', Rule::in(array_map(fn ($t) => $t->value, InventoryMovementType::adjustmentTypes()))],
            // Signed, non-zero - the delta to apply (e.g. -3 for 3 damaged
            // units, +2 if a physical count found more than recorded).
            'quantity_change' => ['required', 'integer', 'not_in:0'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
