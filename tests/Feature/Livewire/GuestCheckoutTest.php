<?php

namespace Tests\Feature\Livewire;

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

    public function test_guest_can_complete_a_booking_and_ends_up_logged_in(): void
    {
        $court = Court::factory()->create(['hourly_rate' => 300]);

        Livewire::test(BookingGrid::class, ['date' => $this->date])
            ->call('toggleSlot', $court->id, $court->name, '09:00:00', '10:00:00')
            ->call('startReview')
            ->set('guestName', 'Juan Dela Cruz')
            ->set('guestEmail', 'juan@example.com')
            ->set('guestPhone', '09171234567')
            ->call('confirmBookings')
            ->assertRedirect(route('bookings.mine'));

        $this->assertAuthenticated();
        $customer = User::where('email', 'juan@example.com')->first();
        $this->assertNotNull($customer);
        $this->assertSame(UserRole::Customer, $customer->role);
        $this->assertSame($customer->id, auth()->id());
        $this->assertSame(1, Booking::where('user_id', $customer->id)->count());
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
            ->assertRedirect(route('bookings.mine'));

        $this->assertSame(1, User::where('email', 'returning@example.com')->count());
        $this->assertSame($existing->id, auth()->id());
        $this->assertSame($existing->id, Booking::first()->user_id);
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
            ->assertRedirect(route('bookings.mine'));

        $this->assertSame($customer->id, Booking::first()->user_id);
    }
}
