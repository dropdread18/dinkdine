<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Exceptions\InventoryAdjustmentException;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The only place a product's stock_quantity should ever change (Store
 * Phase 3 spec: inventory is never "just a manually editable number" -
 * every change gets a matching InventoryMovement row). Both the product's
 * own counter and the movement ledger update together in one locked
 * transaction, so they can never drift apart even under concurrent
 * requests (same lockForUpdate() pattern BookingService uses to prevent
 * double-booking).
 */
class InventoryService
{
    /**
     * @throws InventoryAdjustmentException
     */
    public function recordMovement(
        Product $product,
        InventoryMovementType $type,
        int $quantityChange,
        User $user,
        ?string $reason = null,
        ?string $notes = null,
        // Set together by a caller that already knows what triggered this
        // movement - e.g. StoreSaleService points a Sale movement back at
        // its StoreSale (reference_type = StoreSale::class, reference_id =
        // $sale->id). Not a real polymorphic relation, just the two plain
        // columns the Store Phase 3 spec calls for.
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): InventoryMovement {
        return DB::transaction(function () use ($product, $type, $quantityChange, $user, $reason, $notes, $referenceType, $referenceId) {
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);

            $previous = $locked->stock_quantity;
            $new = $previous + $quantityChange;

            if ($new < 0) {
                throw new InventoryAdjustmentException(
                    "This would leave \"{$locked->name}\" at a negative stock quantity ({$new}). Check the amount and try again."
                );
            }

            $locked->update(['stock_quantity' => $new]);

            return InventoryMovement::create([
                'product_id' => $locked->id,
                'type' => $type,
                'quantity' => $quantityChange,
                'previous_quantity' => $previous,
                'new_quantity' => $new,
                'reason' => $reason,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'user_id' => $user->id,
                'notes' => $notes,
            ]);
        });
    }
}
