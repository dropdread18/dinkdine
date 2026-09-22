<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    case StockIn = 'stock_in';
    // Auto-generated when a POS sale is completed (Store Phase 4) -
    // never manually selectable on the Inventory forms built in Phase 3.
    case Sale = 'sale';
    case Adjustment = 'adjustment';
    case Damaged = 'damaged';
    case Expired = 'expired';
    case Lost = 'lost';
    // Reserved for future functionality (Store Phase 3 spec) - not
    // reachable from any form yet.
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::StockIn => 'Stock In',
            self::Sale => 'Sale',
            self::Adjustment => 'Adjustment',
            self::Damaged => 'Damaged',
            self::Expired => 'Expired',
            self::Lost => 'Lost',
            self::Returned => 'Returned',
        };
    }

    /**
     * Selectable on the manual "Adjust Stock" form - Stock In has its own
     * simpler dedicated form, and Sale/Returned aren't user-initiated yet.
     */
    public static function adjustmentTypes(): array
    {
        return [self::Adjustment, self::Damaged, self::Expired, self::Lost];
    }
}
