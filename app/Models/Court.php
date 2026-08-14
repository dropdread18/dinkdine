<?php

namespace App\Models;

use App\Enums\CourtStatus;
use Database\Factories\CourtFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'court_number', 'hourly_rate', 'status', 'court_type', 'location', 'image', 'sort_order'])]
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
