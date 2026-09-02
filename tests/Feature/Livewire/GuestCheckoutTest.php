<?php

namespace Tests\Feature\Livewire;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Livewire\BookingGrid;
use App\Models\Booking;
use App\Models\BusinessHour;
use App\Models\Court;
use App\Models\PaymentMethod;
use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_selecting_a_slot_shows_the_convenience_fee_as_its_own_line_and_in_the_total(): void
    {
        Setting::set('convenience_fee', '15');
        $court = Court::factory()->create(['hourly_rate' => 300]);

        Livewire::test(BookingGrid::class, ['date' => $this->date])
            ->call('toggleSlot', $court->id, $court->name, '09:00:00', '10:00:00')
            ->assertSee('Convenience Fee')
            ->assertSee('₱15.00')
            ->assertSee('₱315.00');
    }

    public function test_no_convenience_fee_line_shows_when_the_setting_is_unset(): void
    {
        $court = Court::factory()->create(['hourly_rate' => 300]);

        Livewire::test(BookingGrid::class, ['date' => $this->date])
            ->call('toggleSlot', $court->id, $court->name, '09:00:00', '10:00:00')
            ->assertDontSee('Convenience Fee')
            ->assertSee('₱300.00');
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
        PaymentMethod::factory()->create(['name' => 'GCash', 'qr_code_path' => 'payment-methods/fake-gcash-qr.png']);
        $court = Court::factory()->create();

        Livewire::test(BookingGrid::class, ['date' => $this->date])
            ->call('toggleSlot', $court->id, $court->name, '09:00:00', '10:00:00')
            ->call('startReview')
            ->set('guestName', 'Juan Dela Cruz')
            ->set('guestEmail', 'juan@example.com')
            ->set('guestPhone', '09171234567')
            ->call('confirmBookings')
            ->assertSee('Scan QR Code')
            ->assertSee('GCash')
            ->assertSee('/storage/payment-methods/fake-gcash-qr.png', false);
    }

    public function test_payment_hold_screen_lists_every_active_payment_method_but_not_inactive_ones(): void
    {
        PaymentMethod::factory()->create(['name' => 'GCash', 'qr_code_path' => 'payment-methods/gcash.png']);
        PaymentMethod::factory()->create(['name' => 'Maya', 'qr_code_path' => 'payment-methods/maya.png']);
        PaymentMethod::factory()->inactive()->create(['name' => 'GoTyme', 'qr_code_path' => 'payment-methods/gotyme.png']);
        $court = Court::factory()->create();

        Livewire::test(BookingGrid::class, ['date' => $this->date])
            ->call('toggleSlot', $court->id, $court->name, '09:00:00', '10:00:00')
            ->call('startReview')
            ->set('guestName', 'Juan Dela Cruz')
            ->set('guestEmail', 'juan@example.com')
            ->set('guestPhone', '09171234567')
            ->call('confirmBookings')
            ->assertSee('GCash')
            ->assertSee('Maya')
            ->assertDontSee('GoTyme');
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

    public function test_a_screenshot_alone_is_no_longer_enough_without_a_reference_number(): void
    {
        // Reversed by owner request: the reference number is now required
        // outright, not "at least one of reference/screenshot" - a
        // screenshot on its own is no longer sufficient to confirm.
        Storage::fake('local');
        $court = Court::factory()->create();

        Livewire::test(BookingGrid::class, ['date' => $this->date])
            ->call('toggleSlot', $court->id, $court->name, '09:00:00', '10:00:00')
            ->call('startReview')
            ->set('guestName', 'Juan Dela Cruz')
            ->set('guestEmail', 'juan@example.com')
            ->set('guestPhone', '09171234567')
            ->call('confirmBookings')
            ->set('paymentProof', UploadedFile::fake()->image('receipt.jpg'))
            ->call('submitPaymentReference')
            ->assertHasErrors('paymentReference');

        $this->assertSame(BookingStatus::Pending, Booking::first()->status);
    }

    public function test_a_reference_number_with_a_screenshot_confirms_payment(): void
    {
        Storage::fake('local');
        $court = Court::factory()->create();

        Livewire::test(BookingGrid::class, ['date' => $this->date])
            ->call('toggleSlot', $court->id, $court->name, '09:00:00', '10:00:00')
            ->call('startReview')
            ->set('guestName', 'Juan Dela Cruz')
            ->set('guestEmail', 'juan@example.com')
            ->set('guestPhone', '09171234567')
            ->call('confirmBookings')
            ->set('paymentReference', 'GCASH-REF-77')
            ->set('paymentProof', UploadedFile::fake()->image('receipt.jpg'))
            ->call('submitPaymentReference')
            ->assertRedirect(route('bookings.confirmation'));

        $booking = Booking::first();
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame('GCASH-REF-77', $booking->payment->reference_number);
        $this->assertNotNull($booking->payment->payment_proof_path);
        Storage::disk('local')->assertExists($booking->payment->payment_proof_path);
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

    public function test_logged_in_customer_is_also_held_pending_payment_not_confirmed_instantly(): void
    {
        // Reversed post-launch (further owner feedback): a known customer
        // account was never proof that payment actually happened, so
        // logged-in checkouts now go through the exact same payment-hold
        // flow as guests. Only staff-created walk-in bookings (a totally
        // separate controller) still confirm instantly.
        $customer = User::factory()->customer()->create();
        $court = Court::factory()->create();

        Livewire::actingAs($customer)
            ->test(BookingGrid::class, ['date' => $this->date])
            ->call('toggleSlot', $court->id, $court->name, '09:00:00', '10:00:00')
            ->call('startReview')
            ->call('confirmBookings')
            ->assertSet('awaitingPayment', true)
            ->assertNoRedirect();

        $booking = Booking::first();
        $this->assertSame(BookingStatus::Pending, $booking->status);
        $this->assertNotNull($booking->hold_expires_at);
    }

    public function test_logged_in_customer_completes_payment_the_same_way_a_guest_does(): void
    {
        $customer = User::factory()->customer()->create();
        $court = Court::factory()->create();

        Livewire::actingAs($customer)
            ->test(BookingGrid::class, ['date' => $this->date])
            ->call('toggleSlot', $court->id, $court->name, '09:00:00', '10:00:00')
            ->call('startReview')
            ->call('confirmBookings')
            ->set('paymentReference', 'GCASH-REF-999')
            ->call('submitPaymentReference')
            ->assertRedirect(route('bookings.confirmation'));

        $booking = Booking::first();
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame('GCASH-REF-999', $booking->payment->reference_number);
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
            ->assertSet('awaitingPayment', true)
            ->assertNoRedirect();

        $this->assertSame($customer->id, Booking::first()->user_id);
    }
}
