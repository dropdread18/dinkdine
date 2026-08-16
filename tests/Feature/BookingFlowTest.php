<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BusinessHour;
use App\Models\Court;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookingFlowTest extends TestCase
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

    public function test_customer_can_view_the_availability_grid(): void
    {
        $customer = User::factory()->customer()->create();
        Court::factory()->create(['name' => 'Court 1']);

        $this->actingAs($customer)
            ->get('/book?date='.$this->date)
            ->assertOk()
            ->assertSee('Court 1')
            ->assertSee('Available');
    }

    public function test_staff_and_admin_cannot_access_the_booking_page(): void
    {
        $this->actingAs(User::factory()->staff()->create())->get('/book')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/book')->assertForbidden();
    }

    public function test_customer_can_view_the_confirmation_page_for_an_available_slot(): void
    {
        $customer = User::factory()->customer()->create();
        $court = Court::factory()->create(['hourly_rate' => 300]);

        $this->actingAs($customer)
            ->get("/book/{$court->id}?date={$this->date}&start_time=09:00:00&end_time=10:00:00")
            ->assertOk()
            ->assertSee($court->name)
            ->assertSee('300.00');
    }

    public function test_customer_can_create_a_booking(): void
    {
        $customer = User::factory()->customer()->create();
        $court = Court::factory()->create(['hourly_rate' => 300]);

        $response = $this->actingAs($customer)->post("/book/{$court->id}", [
            'date' => $this->date,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'notes' => 'First game',
        ]);

        $booking = Booking::first();
        $response->assertRedirect(route('bookings.show', $booking));
        $this->assertSame($customer->id, $booking->user_id);
        $this->assertSame($court->id, $booking->court_id);
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame('First game', $booking->notes);
    }

    public function test_booking_an_already_taken_slot_fails_gracefully(): void
    {
        $court = Court::factory()->create();
        Booking::factory()->create([
            'court_id' => $court->id,
            'booking_date' => $this->date,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);

        $customer = User::factory()->customer()->create();
        $response = $this->actingAs($customer)->post("/book/{$court->id}", [
            'date' => $this->date,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);

        $response->assertSessionHasErrors('booking');
        $this->assertDatabaseCount('bookings', 1);
        $this->assertSame(0, Booking::where('user_id', $customer->id)->count());
    }

    public function test_a_customer_cannot_view_another_customers_booking(): void
    {
        $owner = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();
        $booking = Booking::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)->get("/bookings/{$booking->id}")->assertNotFound();
    }

    public function test_staff_and_admin_can_view_any_booking(): void
    {
        $booking = Booking::factory()->create();

        $this->actingAs(User::factory()->staff()->create())->get("/bookings/{$booking->id}")->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get("/bookings/{$booking->id}")->assertOk();
    }

    public function test_customer_sees_only_their_own_bookings_in_my_bookings(): void
    {
        $customer = User::factory()->customer()->create();
        $mine = Booking::factory()->create(['user_id' => $customer->id, 'booking_date' => $this->date]);
        Booking::factory()->create(); // someone else's

        $response = $this->actingAs($customer)->get('/my-bookings');

        $response->assertOk();
        $response->assertSee($mine->court->name);
        $this->assertSame(1, Booking::where('user_id', $customer->id)->count());
    }

    public function test_a_customer_can_view_their_own_payment_proof(): void
    {
        Storage::fake('local');
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create(['user_id' => $customer->id]);
        $path = UploadedFile::fake()->image('receipt.jpg')->store('payment-proofs', 'local');
        Payment::factory()->for($booking)->create(['payment_proof_path' => $path]);

        $this->actingAs($customer)->get("/bookings/{$booking->id}/payment-proof")->assertOk();
    }

    public function test_a_customer_cannot_view_another_customers_payment_proof(): void
    {
        Storage::fake('local');
        $owner = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();
        $booking = Booking::factory()->create(['user_id' => $owner->id]);
        $path = UploadedFile::fake()->image('receipt.jpg')->store('payment-proofs', 'local');
        Payment::factory()->for($booking)->create(['payment_proof_path' => $path]);

        $this->actingAs($other)->get("/bookings/{$booking->id}/payment-proof")->assertNotFound();
    }

    public function test_staff_can_view_any_customers_payment_proof(): void
    {
        Storage::fake('local');
        $staff = User::factory()->staff()->create();
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create(['user_id' => $customer->id]);
        $path = UploadedFile::fake()->image('receipt.jpg')->store('payment-proofs', 'local');
        Payment::factory()->for($booking)->create(['payment_proof_path' => $path]);

        $this->actingAs($staff)->get("/bookings/{$booking->id}/payment-proof")->assertOk();
    }

    public function test_payment_proof_route_404s_when_no_screenshot_was_uploaded(): void
    {
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create(['user_id' => $customer->id]);
        Payment::factory()->for($booking)->create(['payment_proof_path' => null]);

        $this->actingAs($customer)->get("/bookings/{$booking->id}/payment-proof")->assertNotFound();
    }
}
