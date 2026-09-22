<?php

namespace App\Models;

use App\Enums\StoreSalePaymentMethod;
use App\Enums\StoreSaleStatus;
use Database\Factories\StoreSaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A completed POS transaction - always created through
 * StoreSaleService::checkout(), never directly, so its items/inventory
 * deduction/sale_number are always consistent. Independent of the
 * booking Payment system (Store Phase 4 spec: a different business
 * domain, not merged in this phase).
 */
#[Fillable(['sale_number', 'subtotal', 'discount', 'total', 'payment_method', 'amount_paid', 'change_amount', 'user_id', 'status'])]
class StoreSale extends Model
{
    /** @use HasFactory<StoreSaleFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'payment_method' => StoreSalePaymentMethod::class,
            'status' => StoreSaleStatus::class,
        ];
    }

    /**
     * @return HasMany<StoreSaleItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(StoreSaleItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
