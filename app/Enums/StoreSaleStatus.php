<?php

namespace App\Enums;

enum StoreSaleStatus: string
{
    case Completed = 'completed';
    // Reserved for a future phase (Store Phase 5+) - no void/refund
    // action exists yet, so these are never written.
    case Voided = 'voided';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Completed => 'Completed',
            self::Voided => 'Voided',
            self::Refunded => 'Refunded',
        };
    }
}
