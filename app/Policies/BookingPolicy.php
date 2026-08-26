<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * A booking's owner, staff, admin, or an Open Play organizer (who
     * only ever gets here via the read-only /manage/bookings schedule)
     * may view it.
     */
    public function view(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id || $user->isStaff() || $user->isAdmin() || $user->isOrganizer();
    }

    /**
     * Self-service cancel/reschedule is owner-only - staff/admin use the
     * separate /manage/bookings routes, which don't go through this check.
     */
    public function cancel(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id;
    }

    public function reschedule(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id;
    }
}
