<?php

namespace Tests\Feature\Livewire;

use App\Enums\BookingStatus;
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

class BookingGridTest extends TestCase
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

    public function test_it_renders_the_grid_for_a_date(): void
    {
        $customer = User::factory()->customer()->create();
        Court::factory()->create(['name' => 'Court 1']);

        Livewire::actingAs($customer)
            ->test(BookingGrid::class, ['date' => $this->date])
            ->assertSee('Court 1')
            ->assertSee('Available');
    }

    public function test_toggling_a_slot_selects_and_deselects_it(): void
    {
        $customer = User::factory()->customer()->create();
        $court = Court::factory()->create(['name' => 'Court 1']);

        $component = Livewire::actingAs($customer)
            ->test(BookingGrid::class, ['date' => $this->date])
            ->call('toggleSlot', $court->id, 'Court 1', '09:00:00', '10:00:00');

        $component->assertSet('selected', ["{$court->id}|09:00:00" => [
            'court_id' => $court->id,
            'court_name' => 'Court 1',
            'date' => $this->date,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]]);

        $component->call('toggleSlot', $court->id, 'Court 1', '09:00:00', '10:00:00');
        $component->assertSet('selected', []);
    }

    public function test_selecting_multiple_slots_across_different_courts(): void
    {
        $customer = User::factory()->customer()->create();
        $courtA = Court::factory()->create();
        $courtB = Court::factory()->create();

        $component = Livewire::actingAs($customer)
            ->test(BookingGrid::class, ['date' => $this->date])
            ->call('toggleSlot', $courtA->id, $courtA->name, '09:00:00', '10:00:00')
            ->call('toggleSlot', $courtB->id, $courtB->name, '14:00:00', '15:00:00');

        $this->assertCount(2, $component->get('selected'));
    }

    public function test_start_review_requires_at_least_one_selection(): void
    {
        $customer = User::factory()->customer()->create();

        Livewire::actingAs($customer)
            ->test(BookingGrid::class, ['date' => $this->date])
            ->call('startReview')
            ->assertSet('reviewing', false);
    }

    public function test_confirming_holds_a_booking_per_selected_slot_pending_payment(): void
    {
        // Owner feedback (post-launch): payment is now required before ANY
        // online booking confirms, logged-in customer or not - not just
        // guest checkouts. confirmBookings() always lands on the payment
        // hold now; see GuestCheckoutTest for the full pay -> Confirmed path.
        $customer = User::factory()->customer()->create();
        $courtA = Court::factory()->create(['hourly_rate' => 300]);
        $courtB = Court::factory()->create(['hourly_rate' => 400]);

        Livewire::actingAs($customer)
            ->test(BookingGrid::class, ['date' => $this->date])
            ->call('toggleSlot', $courtA->id, $courtA->name, '09:00:00', '10:00:00')
            ->call('toggleSlot', $courtB->id, $courtB->name, '11:00:00', '12:00:00')
            ->call('startReview')
            ->assertSet('reviewing', true)
            ->set('notes', 'Birthday game')
            ->call('confirmBookings')
            ->assertSet('awaitingPayment', true)
            ->assertNoRedirect();

        $this->assertSame(2, Booking::where('user_id', $customer->id)->count());
        $this->assertSame(2, Booking::where('notes', 'Birthday game')->count());
        $bookings = Booking::where('user_id', $customer->id)->get();
        $this->assertTrue($bookings->every(fn (Booking $b) => $b->status === BookingStatus::Pending));
        $this->assertTrue($bookings->every(fn (Booking $b) => $b->hold_expires_at !== null));
    }

    public function test_confirming_with_a_conflicting_slot_rolls_back_the_whole_batch_and_shows_an_error(): void
    {
        $customer = User::factory()->customer()->create();
        $courtA = Court::factory()->create();
        $courtB = Court::factory()->create();

        // Someone else grabs courtB's slot after this customer selected it,
        // but before they confirm.
        Booking::factory()->create([
            'court_id' => $courtB->id, 'booking_date' => $this->date, 'start_time' => '11:00:00', 'end_time' => '12:00:00',
        ]);

        Livewire::actingAs($customer)
            ->test(BookingGrid::class, ['date' => $this->date])
            ->call('toggleSlot', $courtA->id, $courtA->name, '09:00:00', '10:00:00')
            ->call('toggleSlot', $courtB->id, $courtB->name, '11:00:00', '12:00:00')
            ->call('startReview')
            ->call('confirmBookings')
            ->assertSet('reviewing', true)
            ->assertSee($courtB->name);

        $this->assertSame(0, Booking::where('user_id', $customer->id)->count());
    }

    public function test_removing_a_slot_from_review_deselects_it(): void
    {
        $customer = User::factory()->customer()->create();
        $court = Court::factory()->create();

        Livewire::actingAs($customer)
            ->test(BookingGrid::class, ['date' => $this->date])
            ->call('toggleSlot', $court->id, $court->name, '09:00:00', '10:00:00')
            ->call('startReview')
            ->call('removeSlot', "{$court->id}|09:00:00")
            ->assertSet('selected', [])
            ->assertSet('reviewing', false);
    }
}
