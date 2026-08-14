<?php

namespace App\Enums;

enum CourtStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Maintenance = 'maintenance';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Maintenance => 'Maintenance',
            self::Closed => 'Closed',
        };
    }

    public function isBookable(): bool
    {
        return $this === self::Active;
    }
}
