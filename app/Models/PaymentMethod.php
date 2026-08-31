<?php

namespace App\Models;

use Database\Factories\PaymentMethodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A bank/e-wallet QR code customers can scan to pay (GCash, Maya, GoTyme,
 * etc.) - replaces the single hardcoded "GCash QR" that used to live as a
 * one-off Setting. Payment stays reference-number based regardless of which
 * method a customer used; this only controls which QR codes are shown on
 * the payment screen.
 */
#[Fillable(['name', 'qr_code_path', 'is_active', 'sort_order'])]
class PaymentMethod extends Model
{
    /** @use HasFactory<PaymentMethodFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
