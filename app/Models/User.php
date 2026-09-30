<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'password', 'role', 'is_active', 'simplified_dashboard'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_set_at' => 'datetime',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'simplified_dashboard' => 'boolean',
        ];
    }

    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isStaff(): bool
    {
        return $this->role === UserRole::Staff;
    }

    public function isOrganizer(): bool
    {
        return $this->role === UserRole::Organizer;
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRole::Customer;
    }

    /**
     * An admin-only flag (owner request) that trims Customers/Staff/Store
     * from that specific account's nav - it doesn't block the routes,
     * just hides links a non-technical owner doesn't need day-to-day.
     */
    public function hasSimplifiedDashboard(): bool
    {
        return $this->isAdmin() && $this->simplified_dashboard;
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
