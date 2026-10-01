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
         * Set only when status is OpenPlay - a key built from the session's
         * date + time window (not its row id), used purely for display:
         * alternating two colors between adjacent-but-different Open Play
         * EVENTS on the same day so customers can tell them apart. Keyed by
         * date+time rather than the row id (or any "batch" grouping tied to
         * how the session was created) so the same event running on
         * multiple courts always reads as one color regardless of whether
         * it was scheduled in one multi-court submission or several
         * separate single-court ones.
         */
        public ?string $openPlayGroupKey = null,
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
        /**
         * Set only when status is Booked or InProgress - the customer's
         * name, for staff/admin views that want to show who booked a slot
         * at a glance instead of a bare "Booked" label. Always populated
         * regardless of viewer (cheap to set, it's already loaded data),
         * so it's each Blade view's own choice whether to render it - the
         * customer-facing reschedule grid deliberately never does, since
         * showing one customer another customer's name would be a real
         * privacy leak.
         */
        public ?string $bookedByName = null,
        /**
         * Set only when status is TrainingSession - the session's own full
         * time range (not this one hourly slot's), same reasoning as
         * openPlayStartTime/openPlayEndTime above.
         */
        public ?string $trainingSessionStartTime = null,
        public ?string $trainingSessionEndTime = null,
        /**
         * Set only when status is TrainingSession - who the drill/coaching
         * session is for. Same staff/admin-only visibility rule as
         * bookedByName (DEC-023: Organizer never sees customer names) -
         * always populated here, it's each Blade view's own choice whether
         * to render it.
         */
        public ?string $trainingCustomerName = null,
        /**
         * Set only when status is TrainingSession - an optional reference
         * link back to this session's record on Reclub. Unlike
         * openPlayLink, this is never shown to customers (there's no
         * signup flow for a Training Session), so every Blade view that
         * renders it is admin/staff-only already.
         */
        public ?string $trainingSessionLink = null,
        /**
         * Set only when status is Tournament - the tournament's own full
         * time range (not this one hourly slot's), same reasoning as
         * openPlayStartTime/openPlayEndTime above.
         */
        public ?string $tournamentStartTime = null,
        public ?string $tournamentEndTime = null,
        /**
         * Set only when status is Tournament - what the tournament is
         * called. Same staff/admin-only visibility rule as
         * trainingCustomerName (gated by $showCustomerNames in the Blade
         * views, even though a tournament name isn't personal data -
         * keeping one consistent gate is simpler than a second one).
         */
        public ?string $tournamentName = null,
        /**
         * Set only when status is Tournament - an optional reference link
         * back to this tournament's record on Reclub, same as
         * trainingSessionLink.
         */
        public ?string $tournamentLink = null,
    ) {}
}
