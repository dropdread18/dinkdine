<?php

namespace Tests\Feature\Notifications;

use App\Models\Booking;
use App\Models\BusinessHour;
use App\Models\Court;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingConfirmed;
use App\Notifications\BookingRescheduled;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\PricingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BookingNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $service;

    private string $date;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new BookingService(new AvailabilityService, new PricingService);

        Setting::set('default_booking_duration_minutes', '60');
        Setting::set('min_booking_notice_minutes', '30');
        Setting::set('max_advance_booking_days', '30');

        $this->date = CarbonImmutable::now()->addDays(2)->toDateString();

        BusinessHour::create([
            'day_of_week' => CarbonImmutable::parse($this->date)->dayOfWeek,
            'opens_at' => '08:00:00',
            'closes_at' => '20:00:00',
            'is_closed' => false,
        ]);
    }

    public function test_booking_sends_a_confirmation_notification(): void
    {
        Notification::fake();

        $user = User::factory()->customer()->create();
        $court = Court::factory()->create();

        $booking = $this->service->book($user, $court, $this->date, '09:00:00', '10:00:00');

        Notification::assertSentTo($user, BookingConfirmed::class, function (BookingConfirmed $n) use ($booking) {
            return $n->booking->id === $booking->id;
        });
    }

    public function test_book_many_only_sends_notifications_after_the_whole_batch_commits(): void
    {
        Notification::fake();

        $user = User::factory()->customer()->create();
        $courtA = Court::factory()->create();
        $courtB = Court::factory()->create();

        // courtB's slot is already taken, so the second slot in the batch
        // will fail and roll back the entire bookMany() call - including
        // the first slot, which "succeeded" until the rollback.
        Booking::factory()->create([
            'court_id' => $courtB->id, 'booking_date' => $this->date, 'start_time' => '11:00:00', 'end_time' => '12:00:00',
        ]);

        try {
            $this->service->bookMany($user, [
                ['court' => $courtA, 'date' => $this->date, 'start_time' => '09:00:00', 'end_time' => '10:00:00'],
                ['court' => $courtB, 'date' => $this->date, 'start_time' => '11:00:00', 'end_time' => '12:00:00'],
            ]);
        } catch (\App\Exceptions\BookingUnavailableException $e) {
            // expected
        }

        // Not even one confirmation should have gone out - proves the
        // afterCommit() callback correctly never fired for the rolled-back
        // first slot, not just that the booking row itself was undone.
        Notification::assertNothingSent();
    }

    public function test_cancel_sends_a_cancellation_notification(): void
    {
        Notification::fake();

        $user = User::factory()->customer()->create();
        $court = Court::factory()->create();
        $booking = $this->service->book($user, $court, $this->date, '09:00:00', '10:00:00');

        $this->service->cancel($booking);

        Notification::assertSentTo($user, BookingCancelled::class);
    }

    public function test_reschedule_sends_a_notification_with_old_and_new_details(): void
    {
        Notification::fake();

        $user = User::factory()->customer()->create();
        $courtA = Court::factory()->create(['name' => 'Court A']);
        $courtB = Court::factory()->create(['name' => 'Court B']);
        $booking = $this->service->book($user, $courtA, $this->date, '09:00:00', '10:00:00');

        $this->service->reschedule($booking, $courtB, $this->date, '14:00:00', '15:00:00');

        Notification::assertSentTo($user, BookingRescheduled::class, function (BookingRescheduled $n) {
            return $n->oldCourtName === 'Court A'
                && $n->oldStartTime === '09:00:00'
                && $n->booking->court->name === 'Court B'
                && $n->booking->start_time === '14:00:00';
        });
    }
}
