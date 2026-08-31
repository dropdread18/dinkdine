<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BusinessHour;
use App\Models\Court;
use App\Models\CourtMaintenance;
use App\Models\User;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CourtMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_and_staff_cannot_access_maintenance(): void
    {
        $this->actingAs(User::factory()->customer()->create())->get('/admin/maintenance')->assertForbidden();
        $this->actingAs(User::factory()->staff()->create())->get('/admin/maintenance')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/maintenance')->assertRedirect('/login');
    }

    public function test_admin_can_view_the_maintenance_list(): void
    {
        $court = Court::factory()->create(['name' => 'Court 9']);
        CourtMaintenance::factory()->create(['court_id' => $court->id, 'reason' => 'Resurfacing']);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/maintenance')
            ->assertOk()
            ->assertSee('Court 9')
            ->assertSee('Resurfacing');
    }

    public function test_admin_can_schedule_a_maintenance_window(): void
    {
        $admin = User::factory()->admin()->create();
        $court = Court::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/maintenance', [
            'court_id' => $court->id,
            'starts_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
            'ends_at' => now()->addDays(3)->addHours(4)->format('Y-m-d\TH:i'),
            'reason' => 'Net replacement',
        ]);

        $response->assertRedirect('/admin/maintenance');
        $this->assertDatabaseHas('court_maintenance', [
            'court_id' => $court->id,
            'reason' => 'Net replacement',
        ]);
    }

    public function test_ends_at_must_be_after_starts_at(): void
    {
        $admin = User::factory()->admin()->create();
        $court = Court::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/maintenance', [
            'court_id' => $court->id,
            'starts_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
            'ends_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
        ]);

        $response->assertSessionHasErrors('ends_at');
    }

    public function test_scheduling_maintenance_over_an_existing_booking_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $court = Court::factory()->create();
        $bookingDate = now()->addDays(5)->toDateString();

        Booking::factory()->create([
            'court_id' => $court->id,
            'booking_date' => $bookingDate,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => BookingStatus::Confirmed,
        ]);

        $response = $this->actingAs($admin)->post('/admin/maintenance', [
            'court_id' => $court->id,
            'starts_at' => $bookingDate.'T09:30',
            'ends_at' => $bookingDate.'T10:30',
        ]);

        $response->assertSessionHasErrors('starts_at');
        $this->assertDatabaseCount('court_maintenance', 0);
    }

    public function test_scheduling_maintenance_around_a_cancelled_booking_is_allowed(): void
    {
        $admin = User::factory()->admin()->create();
        $court = Court::factory()->create();
        $bookingDate = now()->addDays(5)->toDateString();

        Booking::factory()->create([
            'court_id' => $court->id,
            'booking_date' => $bookingDate,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => BookingStatus::Cancelled,
        ]);

        $response = $this->actingAs($admin)->post('/admin/maintenance', [
            'court_id' => $court->id,
            'starts_at' => $bookingDate.'T09:30',
            'ends_at' => $bookingDate.'T10:30',
        ]);

        $response->assertRedirect('/admin/maintenance');
        $this->assertDatabaseCount('court_maintenance', 1);
    }

    public function test_admin_can_update_a_maintenance_window(): void
    {
        $admin = User::factory()->admin()->create();
        $maintenance = CourtMaintenance::factory()->create();

        $response = $this->actingAs($admin)->put("/admin/maintenance/{$maintenance->id}", [
            'court_id' => $maintenance->court_id,
            'starts_at' => now()->addDays(10)->format('Y-m-d\TH:i'),
            'ends_at' => now()->addDays(10)->addHours(2)->format('Y-m-d\TH:i'),
            'reason' => 'Rescheduled resurfacing',
        ]);

        $response->assertRedirect('/admin/maintenance');
        $this->assertSame('Rescheduled resurfacing', $maintenance->fresh()->reason);
    }

    public function test_admin_can_delete_a_maintenance_window(): void
    {
        $admin = User::factory()->admin()->create();
        $maintenance = CourtMaintenance::factory()->create();

        $response = $this->actingAs($admin)->delete("/admin/maintenance/{$maintenance->id}");

        $response->assertRedirect('/admin/maintenance');
        $this->assertDatabaseMissing('court_maintenance', ['id' => $maintenance->id]);
    }

    public function test_scheduled_maintenance_marks_slots_as_maintenance_on_the_availability_grid(): void
    {
        $court = Court::factory()->create();
        $date = now()->addDays(4)->toDateString();

        BusinessHour::updateOrCreate(
            ['day_of_week' => Carbon::parse($date)->dayOfWeek],
            ['opens_at' => '06:00:00', 'closes_at' => '22:00:00', 'is_closed' => false],
        );

        CourtMaintenance::factory()->create([
            'court_id' => $court->id,
            'starts_at' => $date.' 09:00:00',
            'ends_at' => $date.' 12:00:00',
        ]);

        $day = (new AvailabilityService)->forDate($date);
        $courtAvailability = collect($day['courts'])->first(fn ($ca) => $ca->court->is($court));
        $slot = collect($courtAvailability->slots)->first(fn ($s) => $s->startTime === '10:00:00');

        $this->assertSame('maintenance', $slot->status->value);
    }
}
