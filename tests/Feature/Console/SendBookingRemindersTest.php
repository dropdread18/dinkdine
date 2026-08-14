<?php

namespace Tests\Feature\Console;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\BookingReminder;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendBookingRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_sends_a_24_hour_reminder_for_a_booking_in_that_window(): void
    {
        Notification::fake();
        $now = CarbonImmutable::parse('2026-09-01 10:00:00');
        CarbonImmutable::setTestNow($now);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::Confirmed,
            'booking_date' => $now->addHours(23)->toDateString(),
            'start_time' => $now->addHours(23)->format('H:i:s'),
            'end_time' => $now->addHours(24)->format('H:i:s'),
        ]);

        $this->artisan('bookings:send-reminders')->assertSuccessful();

        Notification::assertSentTo($booking->user, BookingReminder::class, fn (BookingReminder $n) => $n->hoursBefore === 24);
        $this->assertNotNull($booking->fresh()->reminder_24h_sent_at);
        $this->assertNull($booking->fresh()->reminder_1h_sent_at);
    }

    public function test_sends_a_1_hour_reminder_for_a_booking_in_that_window(): void
    {
        Notification::fake();
        $now = CarbonImmutable::parse('2026-09-01 10:00:00');
        CarbonImmutable::setTestNow($now);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::Confirmed,
            'booking_date' => $now->addMinutes(30)->toDateString(),
            'start_time' => $now->addMinutes(30)->format('H:i:s'),
            'end_time' => $now->addMinutes(90)->format('H:i:s'),
        ]);

        $this->artisan('bookings:send-reminders')->assertSuccessful();

        Notification::assertSentTo($booking->user, BookingReminder::class, fn (BookingReminder $n) => $n->hoursBefore === 1);
        $this->assertNotNull($booking->fresh()->reminder_1h_sent_at);
    }

    public function test_does_not_send_a_reminder_outside_either_window(): void
    {
        Notification::fake();
        $now = CarbonImmutable::parse('2026-09-01 10:00:00');
        CarbonImmutable::setTestNow($now);

        Booking::factory()->create([
            'status' => BookingStatus::Confirmed,
            'booking_date' => $now->addDays(5)->toDateString(),
            'start_time' => $now->addDays(5)->format('H:i:s'),
            'end_time' => $now->addDays(5)->addHour()->format('H:i:s'),
        ]);

        $this->artisan('bookings:send-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_does_not_send_a_reminder_for_a_cancelled_booking(): void
    {
        Notification::fake();
        $now = CarbonImmutable::parse('2026-09-01 10:00:00');
        CarbonImmutable::setTestNow($now);

        Booking::factory()->cancelled()->create([
            'booking_date' => $now->addHours(23)->toDateString(),
            'start_time' => $now->addHours(23)->format('H:i:s'),
            'end_time' => $now->addHours(24)->format('H:i:s'),
        ]);

        $this->artisan('bookings:send-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_running_twice_does_not_send_the_same_reminder_twice(): void
    {
        Notification::fake();
        $now = CarbonImmutable::parse('2026-09-01 10:00:00');
        CarbonImmutable::setTestNow($now);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::Confirmed,
            'booking_date' => $now->addHours(23)->toDateString(),
            'start_time' => $now->addHours(23)->format('H:i:s'),
            'end_time' => $now->addHours(24)->format('H:i:s'),
        ]);

        $this->artisan('bookings:send-reminders');
        $this->artisan('bookings:send-reminders');

        Notification::assertSentToTimes($booking->user, BookingReminder::class, 1);
    }

    public function test_does_not_send_a_reminder_for_a_booking_that_already_started(): void
    {
        Notification::fake();
        $now = CarbonImmutable::parse('2026-09-01 10:00:00');
        CarbonImmutable::setTestNow($now);

        Booking::factory()->create([
            'status' => BookingStatus::Confirmed,
            'booking_date' => $now->toDateString(),
            'start_time' => $now->subMinutes(30)->format('H:i:s'),
            'end_time' => $now->addMinutes(30)->format('H:i:s'),
        ]);

        $this->artisan('bookings:send-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }
}
