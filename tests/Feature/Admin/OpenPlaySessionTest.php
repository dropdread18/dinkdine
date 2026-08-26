<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BusinessHour;
use App\Models\Court;
use App\Models\OpenPlaySession;
use App\Models\Setting;
use App\Models\User;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OpenPlaySessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_and_staff_cannot_access_open_play(): void
    {
        $this->actingAs(User::factory()->customer()->create())->get('/admin/open-play')->assertForbidden();
        $this->actingAs(User::factory()->staff()->create())->get('/admin/open-play')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/open-play')->assertRedirect('/login');
    }

    public function test_admin_can_view_the_open_play_list(): void
    {
        $court = Court::factory()->create(['name' => 'Court 9']);
        OpenPlaySession::factory()->create(['court_id' => $court->id, 'notes' => 'Ladder night']);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/open-play')
            ->assertOk()
            ->assertSee('Court 9')
            ->assertSee('Ladder night');
    }

    public function test_admin_can_schedule_an_open_play_session(): void
    {
        $admin = User::factory()->admin()->create();
        $court = Court::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/open-play', [
            'court_ids' => [$court->id],
            'session_date' => now()->addDays(3)->toDateString(),
            'start_time' => '18:00:00',
            'end_time' => '20:00:00',
            'notes' => 'Community night',
        ]);

        $response->assertRedirect('/admin/open-play');
        $this->assertDatabaseHas('open_play_sessions', [
            'court_id' => $court->id,
            'notes' => 'Community night',
            'created_by' => $admin->id,
        ]);
    }

    public function test_admin_can_schedule_open_play_across_multiple_courts_at_once(): void
    {
        $admin = User::factory()->admin()->create();
        $indoor = Court::factory()->create(['name' => 'Indoor Court']);
        $outdoor = Court::factory()->create(['name' => 'Outdoor Court']);

        $response = $this->actingAs($admin)->post('/admin/open-play', [
            'court_ids' => [$indoor->id, $outdoor->id],
            'session_date' => now()->addDays(3)->toDateString(),
            'start_time' => '18:00:00',
            'end_time' => '20:00:00',
            'notes' => 'Community night',
        ]);

        $response->assertRedirect('/admin/open-play');
        $this->assertDatabaseCount('open_play_sessions', 2);
        $this->assertDatabaseHas('open_play_sessions', ['court_id' => $indoor->id, 'notes' => 'Community night']);
        $this->assertDatabaseHas('open_play_sessions', ['court_id' => $outdoor->id, 'notes' => 'Community night']);
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        $admin = User::factory()->admin()->create();
        $court = Court::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/open-play', [
            'court_ids' => [$court->id],
            'session_date' => now()->addDays(3)->toDateString(),
            'start_time' => '20:00:00',
            'end_time' => '18:00:00',
        ]);

        $response->assertSessionHasErrors('end_time');
    }

    public function test_scheduling_open_play_over_an_existing_booking_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $court = Court::factory()->create();
        $date = now()->addDays(5)->toDateString();

        Booking::factory()->create([
            'court_id' => $court->id,
            'booking_date' => $date,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => BookingStatus::Confirmed,
        ]);

        $response = $this->actingAs($admin)->post('/admin/open-play', [
            'court_ids' => [$court->id],
            'session_date' => $date,
            'start_time' => '09:30:00',
            'end_time' => '10:30:00',
        ]);

        $response->assertSessionHasErrors('court_ids');
        $this->assertDatabaseCount('open_play_sessions', 0);
    }

    public function test_scheduling_open_play_over_another_open_play_session_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $court = Court::factory()->create();
        $date = now()->addDays(5)->toDateString();

        OpenPlaySession::factory()->create([
            'court_id' => $court->id,
            'session_date' => $date,
            'start_time' => '18:00:00',
            'end_time' => '20:00:00',
        ]);

        $response = $this->actingAs($admin)->post('/admin/open-play', [
            'court_ids' => [$court->id],
            'session_date' => $date,
            'start_time' => '19:00:00',
            'end_time' => '21:00:00',
        ]);

        $response->assertSessionHasErrors('court_ids');
        $this->assertDatabaseCount('open_play_sessions', 1);
    }

    public function test_scheduling_across_multiple_courts_rejects_the_whole_batch_if_one_conflicts(): void
    {
        $admin = User::factory()->admin()->create();
        $indoor = Court::factory()->create();
        $outdoor = Court::factory()->create();
        $date = now()->addDays(5)->toDateString();

        Booking::factory()->create([
            'court_id' => $outdoor->id,
            'booking_date' => $date,
            'start_time' => '18:00:00',
            'end_time' => '19:00:00',
            'status' => BookingStatus::Confirmed,
        ]);

        $response = $this->actingAs($admin)->post('/admin/open-play', [
            'court_ids' => [$indoor->id, $outdoor->id],
            'session_date' => $date,
            'start_time' => '18:00:00',
            'end_time' => '20:00:00',
        ]);

        $response->assertSessionHasErrors('court_ids');
        $this->assertDatabaseCount('open_play_sessions', 0);
    }

    public function test_admin_can_update_an_open_play_session_without_conflicting_with_itself(): void
    {
        $admin = User::factory()->admin()->create();
        $session = OpenPlaySession::factory()->create();

        $response = $this->actingAs($admin)->put("/admin/open-play/{$session->id}", [
            'court_id' => $session->court_id,
            'session_date' => $session->session_date->toDateString(),
            'start_time' => '19:00:00',
            'end_time' => '21:00:00',
            'notes' => 'Rescheduled',
        ]);

        $response->assertRedirect('/admin/open-play');
        $this->assertSame('Rescheduled', $session->fresh()->notes);
    }

    public function test_admin_can_delete_an_open_play_session(): void
    {
        $admin = User::factory()->admin()->create();
        $session = OpenPlaySession::factory()->create();

        $response = $this->actingAs($admin)->delete("/admin/open-play/{$session->id}");

        $response->assertRedirect('/admin/open-play');
        $this->assertDatabaseMissing('open_play_sessions', ['id' => $session->id]);
    }

    public function test_open_play_session_shows_as_open_play_on_the_availability_grid(): void
    {
        $court = Court::factory()->create();
        $date = now()->addDays(4)->toDateString();

        BusinessHour::updateOrCreate(
            ['day_of_week' => Carbon::parse($date)->dayOfWeek],
            ['opens_at' => '06:00:00', 'closes_at' => '22:00:00', 'is_closed' => false],
        );

        OpenPlaySession::factory()->create([
            'court_id' => $court->id,
            'session_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
        ]);

        $day = (new AvailabilityService)->forDate($date);
        $courtAvailability = collect($day['courts'])->first(fn ($ca) => $ca->court->is($court));
        $slot = collect($courtAvailability->slots)->first(fn ($s) => $s->startTime === '10:00:00');

        $this->assertSame('open_play', $slot->status->value);
    }

    public function test_a_regular_booking_attempt_over_an_open_play_slot_is_rejected(): void
    {
        Setting::set('default_booking_duration_minutes', '60');
        Setting::set('min_booking_notice_minutes', '0');
        Setting::set('max_advance_booking_days', '30');

        $court = Court::factory()->create();
        $date = now()->addDays(4)->toDateString();

        BusinessHour::updateOrCreate(
            ['day_of_week' => Carbon::parse($date)->dayOfWeek],
            ['opens_at' => '06:00:00', 'closes_at' => '22:00:00', 'is_closed' => false],
        );

        OpenPlaySession::factory()->create([
            'court_id' => $court->id,
            'session_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
        ]);

        $user = User::factory()->customer()->create();
        $bookingService = app(\App\Services\BookingService::class);

        $this->expectException(\App\Exceptions\BookingUnavailableException::class);
        $bookingService->book($user, $court, $date, '10:00:00', '11:00:00');
    }
}
