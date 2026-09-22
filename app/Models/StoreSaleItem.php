<?php

namespace App\Models;

use Database\Factories\StoreSaleItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of a completed sale. product_name/unit_price/cost_price are a
 * frozen snapshot from checkout time - never re-derived from the live
 * Product later (Store Phase 4 spec), so a price change or the product
 * being deactivated/renamed can never alter what an old receipt shows.
 */
#[Fillable(['store_sale_id', 'product_id', 'product_name', 'quantity', 'unit_price', 'cost_price', 'subtotal'])]
class StoreSaleItem extends Model
{
    /** @use HasFactory<StoreSaleItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<StoreSale, $this>
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(StoreSale::class, 'store_sale_id');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
