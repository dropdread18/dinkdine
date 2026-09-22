<?php

namespace App\Enums;

/**
 * Recording only - no payment gateway integration (Store Phase 4 spec).
 * Deliberately a separate enum from the booking Payment system's
 * free-text `method` column: Store is a new, independent domain (see
 * the "don't merge Store Sales into the booking Payment model" note in
 * the handoff spec), so it gets its own validated, fixed set of methods
 * rather than inheriting that older, looser convention.
 */
enum StoreSalePaymentMethod: string
{
    case Cash = 'cash';
    case GCash = 'gcash';
    case BankTransfer = 'bank_transfer';
    case Card = 'card';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::GCash => 'GCash',
            self::BankTransfer => 'Bank Transfer',
            self::Card => 'Card',
            self::Other => 'Other',
        };
    }
}
