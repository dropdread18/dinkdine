<?php

namespace App\Models;

use App\Enums\CourtStatus;
use Database\Factories\CourtFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * `hourly_rate` and `evening_hourly_rate` are the day (6:00 AM-5:00 PM) and
 * evening (5:00 PM onwards) rates - the column wasn't renamed when evening
 * pricing was added, to avoid touching the ~20 other files that already
 * reference `hourly_rate`, but it specifically means the DAY rate now. See
 * PricingService::calculate() for how a slot that straddles 5:00 PM is
 * priced (proportionally split, not just "whichever rate the start time
 * falls under").
 */
#[Fillable(['name', 'description', 'court_number', 'hourly_rate', 'evening_hourly_rate', 'status', 'court_type', 'location', 'image', 'sort_order'])]
class Court extends Model
{
    /** @use HasFactory<CourtFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
            'evening_hourly_rate' => 'decimal:2',
            'status' => CourtStatus::class,
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @return HasMany<CourtMaintenance, $this>
     */
    public function maintenancePeriods(): HasMany
    {
        return $this->hasMany(CourtMaintenance::class);
    }

    public function isBookable(): bool
    {
        return $this->status->isBookable();
    }
}
