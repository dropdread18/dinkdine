<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Enums\StoreSalePaymentMethod;
use App\Exceptions\InventoryAdjustmentException;
use App\Exceptions\StoreSaleException;
use App\Models\Product;
use App\Models\StoreSale;
use App\Models\StoreSaleItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The only place a POS sale should ever be created (same role
 * BookingService plays for court bookings). One all-or-nothing
 * transaction: verify stock, create the sale and its items, deduct
 * inventory through InventoryService for every line - either the whole
 * thing lands, or none of it does (Store Phase 4 spec §20).
 */
class StoreSaleService
{
    public function __construct(
        private readonly InventoryService $inventory,
    ) {}

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     *
     * @throws StoreSaleException
     */
    public function checkout(
        User $cashier,
        array $items,
        StoreSalePaymentMethod $paymentMethod,
        float $amountPaid,
        float $discount = 0,
    ): StoreSale {
        if (empty($items)) {
            throw new StoreSaleException('Select at least one product.');
        }

        return DB::transaction(function () use ($cashier, $items, $paymentMethod, $amountPaid, $discount) {
            // Locked up front, in the same order every time (by product
            // id) - the only thing that would otherwise risk a deadlock
            // against a concurrent checkout sharing a product is two
            // transactions locking the same rows in different orders.
            $productIds = collect($items)->pluck('product_id')->unique()->sort()->values();
            $products = Product::query()->lockForUpdate()->whereIn('id', $productIds)->get()->keyBy('id');

            $subtotal = 0;
            $lines = [];

            foreach ($items as $line) {
                $product = $products->get($line['product_id']);

                if (! $product || ! $product->is_active) {
                    throw new StoreSaleException('One of the selected products is no longer available. Please start over.');
                }

                $quantity = $line['quantity'];

                if ($quantity < 1) {
                    throw new StoreSaleException("Invalid quantity for \"{$product->name}\".");
                }

                // Server-side stock check, not just a client-side guard
                // (Store Phase 4 spec §21) - the locked read above means
                // no concurrent checkout can slip past this between the
                // read and this sale committing.
                if ($quantity > $product->stock_quantity) {
                    throw new StoreSaleException(
                        "Only {$product->stock_quantity} {$product->unit} of \"{$product->name}\" available, but {$quantity} were requested."
                    );
                }

                $lineSubtotal = $product->selling_price * $quantity;
                $subtotal += $lineSubtotal;

                $lines[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $product->selling_price,
                    'cost_price' => $product->cost_price,
                    'subtotal' => $lineSubtotal,
                ];
            }

            $total = $subtotal - $discount;

            if ($total < 0) {
                throw new StoreSaleException('The discount cannot be greater than the subtotal.');
            }

            if ($amountPaid < $total) {
                throw new StoreSaleException('Amount paid is less than the total due.');
            }

            $sale = StoreSale::create([
                // A temporary, already-unique placeholder - immediately
                // replaced below once the row's own auto-increment id is
                // known. Never a racy MAX(id)+1 read, so this is unique
                // even under concurrent checkouts with no extra locking.
                'sale_number' => (string) Str::uuid(),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'payment_method' => $paymentMethod,
                'amount_paid' => $amountPaid,
                'change_amount' => $amountPaid - $total,
                'user_id' => $cashier->id,
            ]);
            $sale->update(['sale_number' => sprintf('SALE-%06d', $sale->id)]);

            foreach ($lines as $line) {
                StoreSaleItem::create([
                    'store_sale_id' => $sale->id,
                    'product_id' => $line['product']->id,
                    'product_name' => $line['product']->name,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'cost_price' => $line['cost_price'],
                    'subtotal' => $line['subtotal'],
                ]);

                try {
                    $this->inventory->recordMovement(
                        product: $line['product'],
                        type: InventoryMovementType::Sale,
                        quantityChange: -$line['quantity'],
                        user: $cashier,
                        reason: "Sold on {$sale->sale_number}",
                        referenceType: StoreSale::class,
                        referenceId: $sale->id,
                    );
                } catch (InventoryAdjustmentException $e) {
                    // The stock check above already accounted for every
                    // line against the same locked rows, so this can only
                    // happen if two lines in this same cart reference the
                    // same product with a combined quantity exceeding
                    // stock - re-thrown as a StoreSaleException so the
                    // caller only ever has one exception type to catch.
                    throw new StoreSaleException($e->getMessage());
                }
            }

            return $sale;
        });
    }
}
