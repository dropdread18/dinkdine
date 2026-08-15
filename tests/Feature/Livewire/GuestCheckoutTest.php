<?php

namespace Tests\Feature\Livewire;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Livewire\BookingGrid;
use App\Models\Booking;
use App\Models\BusinessHour;
use App\Models\Court;
use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GuestCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private string $date;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_guest_can_view_the_booking_page(): void
    {
        $this->get('/book?date='.$this->date)->assertOk();
    }

    public function test_staff_cannot_view_the_booking_page(): void
    {
        $this->actingAs(User::factory()->staff()->create())->get('/book')->assertForbidden();
    }

    public function test_admin_cannot_view_the_booking_page(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/book')->assertForbidden();
    }

    public function test_guest_checkout_holds_the_slot_pending_awaiting_payment(): void
    {
        $court = Court::factory()->create(['hourly_rate' => 300]);

        $component = Livewire::test(BookingGrid::class, ['date' => $this->date])
            ->call('toggleSlot', $court->id, $court->name, '09:00:00', '10:00:00')
            ->call('startReview')
            ->set('guestName', 'Juan Dela Cruz')
            ->set('guestEmail', 'juan@example.com')
            ->set('guestPhone', '09171234567')
            ->call('confirmBookings')
            ->assertSet('awaitingPayment', true)
            ->assertNoRedirect();

        // Logged in already (so the session survives a refresh while they
        // pay) even though the booking itself isn't Confirmed yet.
        $this->assertAuthenticated();
        $customer = User::where('email', 'juan@example.com')->first();
        $this->assertNotNull($customer);
        $this->assertSame(UserRole::Customer, $customer->role);
        $this->assertSame($customer->id, auth()->id());

        $booking = Booking::where('user_id', $customer->id)->first();
        $this->assertNotNull($booking);
        $this->assertSame(BookingStatus::Pending, $booking->status);
        $this->assertNotNull($booking->hold_expires_at);
        $this->assertTrue($booking->hold_expires_at->isFuture());
        $this->assertSame([$booking->id], $component->get('pendingBookingIds'));
    }

    public function test_guest_can_complete_a_booking_by_submitting_a_payment_reference(): void
    {
        $court = Court::factory()->create(['hourly_rate' => 300]);

        Livewire::test(BookingGrid::class, ['date' => $this->date])
            ->call('toggleSlot', $court->id, $court->name, '09:00:00', '10:00:00')
            ->call('startReview')
            ->set('guestName', 'Juan Dela Cruz')
            ->set('guestEmail', 'juan@example.com')
            ->set('guestPhone', '09171234567')
            ->call('confirmBookings')
            ->set('paymentReference', 'GCASH-REF-12345')
            ->call('submitPaymentReference')
            ->assertRedirect(route('bookings.confirmation'));

        $booking = Booking::first();
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame(PaymentStatus::Pending, $booking->payment->status);
        $this->assertSame('GCASH-REF-12345', $booking->payment->reference_number);
    }

    public function test_payment_hold_screen_shows_the_qr_code_when_one_is_configured(): void
    {
        Setting::set('payment_qr_code', 'settings/fake-qr.png');
        $court = Court::factory()->create();

        Livewire::test(BookingGrid::class, ['date' => $this->date])
            ->call('toggleSlot', $court->id, $court->name, '09:00:00', '10:00:00')
            ->call('startReview')
            ->set('guestName', 'Juan Dela Cruz')
            ->set('guestEmail', 'juan@example.com')
            ->set('guestPhone', '09171234567')
            ->call('confirmBookings')
            ->assertSee('Scan QR Code')
            ->assertSee('/storage/settings/fake-qr.png', false);
    }

    public function test_payment_hold_screen_has_no_scan_toggle_without_a_configured_qr_code(): void
    {
        $court = Court::factory()->create();

        Livewire::test(BookingGrid::class, ['date' => $this->date])
            ->call('toggleSlot', $court->id, $court->name, '09:00:00', '10:00:00')
            ->call('startReview')
            ->set('guestName', 'Juan Dela Cruz')
            ->set('guestEmail', 'juan@example.com')
            ->set('guestPhone', '09171234567')
            ->call('confirmBookings')
            ->assertDontSee('Scan QR Code');
    }

    public function test_submitting_payment_reference_requires_a_value(): void
    {
        $court = Court::factory()->create();

        Livewire::test(BookingGrid::class, ['date' => $this->date])
            ->call('toggleSlot', $court->id, $court->name, '09:00:00', '10:00:00')
            ->call('startReview')
            ->set('guestName', 'Juan Dela Cruz')
            ->set('guestEmail', 'juan@example.com')
            ->set('guestPhone', '09171234567')
            ->call('confirmBookings')
            ->call('submitPaymentReference')
            ->assertHasErrors('paymentReference');

        $this->assertSame(BookingStatus::Pending, Booking::first()->status);
    }

    public function test_submitting_a_reference_after_the_hold_expired_fails_and_resets_to_the_grid(): void
    {
        $court = Court::factory()->create();

        $component = Livewire::test(BookingGrid::class, ['date' => $this->date])
            ->call('toggleSlot', $court->id, $court->name, '09:00:00', '10:00:00')
            ->call('startReview')
            ->set('guestName', 'Juan Dela Cruz')
            ->set('guestEmail', 'juan@example.com')
            ->set('guestPhone', '09171234567')
            ->call('confirmBookings');

        Booking::first()->forceFill(['hold_expires_at' => now()->subMinute()])->save();

        $component->set('paymentReference', 'GCASH-REF-12345')
            ->call('submitPaymentReference')
            ->assertSet('awaitingPayment', false)
            ->assertSet('selected', [])
            ->assertNoRedirect();

        $this->assertSame(BookingStatus::Pending, Booking::first()->status);
    }

    public function test_logged_in_customer_is_still_confirmed_instantly_not_held(): void
    {
        $customer = User::factory()->customer()->create();
        $court = Court::factory()->create();

        Livewire::actingAs($customer)
            ->test(BookingGrid::class, ['date' => $this->date])
            ->call('toggleSlot', $court->id, $court->name, '09:00:00', '10:00:00')
            ->call('startReview')
            ->call('confirmBookings')
            ->assertRedirect(route('bookings.confirmation'));

        $booking = Booking::first();
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertNull($booking->hold_expires_at);
    }

    public function test_guest_checkout_requires_name_email_and_phone(): void
    {
        $court = Court::factory()->create();

        Livewire::test(BookingGrid::class, ['date' => $this->date])
            ->call('toggleSlot', $court->id, $court->name, '09:00:00', '10:00:00')
            ->call('startReview')
            ->call('confirmBookings')
            ->assertSet('reviewing', true);

        $this->assertGuest();
        $this->assertSame(0, Booking::count());
    }

    public function test_a_returning_guest_reuses_their_existing_customer_account(): void
    {
        $existing = User::factory()->customer()->create(['email' => 'returning@example.com', 'name' => 'Returning Guest']);
        $court = Court::factory()->create();

        Livewire::test(BookingGrid::class, ['date' => $this->date])
            ->call('toggleSlot', $court->id, $court->name, '09:00:00', '10:00:00')
            ->call('startReview')
            ->set('guestName', 'Different Name Typed In')
            ->set('guestEmail', 'returning@example.com')
            ->set('guestPhone', '09171234567')
            ->call('confirmBookings')
            ->assertSet('awaitingPayment', true);

        $this->assertSame(1, User::where('email', 'returning@example.com')->count());
        $this->assertSame($existing->id, auth()->id());
        $this->assertSame($existing->id, Booking::first()->user_id);
        $this->assertSame(BookingStatus::Pending, Booking::first()->status);
    }

    public function test_guest_checkout_rejects_an_email_belonging_to_a_staff_account(): void
    {
        $staff = User::factory()->staff()->create(['email' => 'staffmember@example.com']);
        $court = Court::factory()->create();

        Livewire::test(BookingGrid::class, ['date' => $this->date])
            ->call('toggleSlot', $court->id, $court->name, '09:00:00', '10:00:00')
            ->call('startReview')
            ->set('guestName', 'Someone')
            ->set('guestEmail', 'staffmember@example.com')
            ->set('guestPhone', '09171234567')
            ->call('confirmBookings')
            ->assertSet('reviewing', true);

        $this->assertGuest();
        $this->assertSame(0, Booking::count());
        $this->assertSame(1, User::where('email', 'staffmember@example.com')->count());
    }

    public function test_logged_in_customer_does_not_need_guest_fields(): void
    {
        $customer = User::factory()->customer()->create();
        $court = Court::factory()->create();

        Livewire::actingAs($customer)
            ->test(BookingGrid::class, ['date' => $this->date])
            ->call('toggleSlot', $court->id, $court->name, '09:00:00', '10:00:00')
            ->call('startReview')
            ->call('confirmBookings')
            ->assertRedirect(route('bookings.confirmation'));

        $this->assertSame($customer->id, Booking::first()->user_id);
    }
}
