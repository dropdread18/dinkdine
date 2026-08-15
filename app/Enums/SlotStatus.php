<?php

namespace App\Enums;

enum SlotStatus: string
{
    case Available = 'available';
    case Booked = 'booked';
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Booked => 'Booked',
            self::Pending => 'Pending',
            self::InProgress => 'In Progress',
            self::Closed => 'Closed',
        };
    }
}
