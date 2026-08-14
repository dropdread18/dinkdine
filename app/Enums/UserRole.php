<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case Staff = 'staff';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Customer',
            self::Staff => 'Staff',
            self::Admin => 'Administrator',
        };
    }
}
