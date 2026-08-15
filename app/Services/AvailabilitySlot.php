<?php

namespace App\Services;

use App\Enums\SlotStatus;

final readonly class AvailabilitySlot
{
    public function __construct(
        public string $startTime,
        public string $endTime,
        public SlotStatus $status,
        public ?int $bookingId = null,
        /**
         * Set only when status is InProgress - lets every viewer (not just
         * the person actively booking) render a live countdown for this
         * slot, not just a static label. ISO 8601, matching the format
         * BookingGrid already passes its own holdExpiresAt to the view in.
         */
        public ?string $holdExpiresAt = null,
    ) {}
}
