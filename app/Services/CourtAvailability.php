<?php

namespace App\Services;

use App\Models\Court;

final readonly class CourtAvailability
{
    /**
     * @param  AvailabilitySlot[]  $slots
     */
    public function __construct(
        public Court $court,
        public array $slots,
    ) {}
}
