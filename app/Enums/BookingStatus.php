<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case NoShow = 'no_show';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Cancelled => 'Cancelled',
            self::Completed => 'Completed',
            self::NoShow => 'No Show',
            self::Expired => 'Expired',
        };
    }

    /**
     * Statuses that still occupy the court and must block availability.
     */
    public function blocksAvailability(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed], true);
    }
}
