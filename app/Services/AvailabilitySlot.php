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
        /**
         * Set only when status is OpenPlay - the underlying OpenPlaySession's
         * id, used purely for display: alternating two colors between
         * adjacent-but-different sessions on the same day/court so
         * customers can tell them apart, not as a real identity check.
         */
        public ?int $openPlaySessionId = null,
        /**
         * Set only when status is OpenPlay - where customers/staff go to
         * actually sign up (organizers run signup off-platform). Null
         * means no link has been added for this session yet.
         */
        public ?string $openPlayLink = null,
        /**
         * Set only when status is OpenPlay - the session's own full time
         * range (not this one hourly slot's), so a click on any hour
         * within e.g. a 3-7pm session shows "3:00 PM - 7:00 PM", not just
         * the single hour that happened to be clicked.
         */
        public ?string $openPlayStartTime = null,
        public ?string $openPlayEndTime = null,
    ) {}
}
