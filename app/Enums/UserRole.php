<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case Staff = 'staff';
    case Admin = 'admin';
    // A restricted back-office role for an Open Play organizer who isn't
    // facility staff - can see the booking schedule and manage Open Play
    // sessions, but never payments/reports/settings/customer or staff
    // management (Requirements per owner: "they don't need to see sales").
    case Organizer = 'organizer';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Customer',
            self::Staff => 'Staff',
            self::Admin => 'Administrator',
            self::Organizer => 'Organizer',
        };
    }
}
