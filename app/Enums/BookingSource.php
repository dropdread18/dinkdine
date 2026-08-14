<?php

namespace App\Enums;

enum BookingSource: string
{
    case Online = 'online';
    case WalkIn = 'walk_in';
    case Staff = 'staff';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::WalkIn => 'Walk-in',
            self::Staff => 'Staff',
            self::Admin => 'Admin',
        };
    }
}
