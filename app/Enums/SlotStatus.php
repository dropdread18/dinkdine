<?php

namespace App\Enums;

enum SlotStatus: string
{
    case Available = 'available';
    case Booked = 'booked';
    case InProgress = 'in_progress';
    case Closed = 'closed';
    case OpenPlay = 'open_play';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Booked => 'Booked',
            self::InProgress => 'In Progress',
            self::Closed => 'Closed',
            self::OpenPlay => 'Open Play',
            self::Maintenance => 'Maintenance',
        };
    }
}
