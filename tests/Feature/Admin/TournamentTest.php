<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BusinessHour;
use App\Models\Court;
use App\Models\OpenPlaySession;
use App\Models\Tournament;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TournamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_and_staff_cannot_access_tournaments(): void
    {
        $this->actingAs(User::factory()->customer()->create())->get('/admin/tournaments')->assertForbidden();
        $this->actingAs(User::factory()->staff()->create())->get('/admin/tournaments')->assertForbidden();
    }

    public function test_organizer_cannot_access_tournaments(): void
    {
        // Same reasoning as Training Session - Tournament is admin-only,
        // Organizer never needs it.
        $this->actingAs(User::factory()->organizer()->create())->get('/admin/tournaments')->assertForbidden();
        $this->actingAs(User::factory()->organizer()->create())->get('/admin/tournaments/create')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/tournaments')->assertRedirect('/login');
    }

    public function test_admin_can_view_the_tournament_list(): void
    {
        $court = Court::factory()->create(['name' => 'Court 9']);
        Tournament::factory()->create(['court_id' => $court->id, 'tournament_name' => 'Summer Smash Cup']);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/tournaments')
            ->assertOk()
            ->assertSee('Court 9')
            ->assertSee('Summer Smash Cup');
    }

    public function test_admin_can_schedule_a_tournament(): void
    {
        $admin = User::factory()->admin()->create();
        $court = Court::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/tournaments', [
            'court_id' => $court->id,
            'tournament_name' => 'Summer Smash Cup',
            'session_date' => now()->addDays(3)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'notes' => 'Bracket posted at the front desk',
        ]);

        $response->assertRedirect('/admin/tournaments');
        $this->assertDatabaseHas('tournaments', [
            'court_id' => $court->id,
            'tournament_name' => 'Summer Smash Cup',
            'notes' => 'Bracket posted at the front desk',
            'created_by' => $admin->id,
        ]);
    }

    public function test_admin_can_set_a_reclub_link_when_scheduling_a_tournament(): void
    {
        $admin = User::factory()->admin()->create();
        $court = Court::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/tournaments', [
            'court_id' => $court->id,
            'tournament_name' => 'Summer Smash Cup',
            'session_date' => now()->addDays(3)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'reclub_link' => 'https://reclub.co/clubs/@example',
        ]);

        $response->assertRedirect('/admin/tournaments');
        $this->assertDatabaseHas('tournaments', [
            'court_id' => $court->id,
            'reclub_link' => 'https://reclub.co/clubs/@example',
        ]);
    }

    public function test_reclub_link_must_be_a_valid_url(): void
    {
        $admin = User::factory()->admin()->create();
        $court = Court::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/tournaments', [
            'court_id' => $court->id,
            'tournament_name' => 'Summer Smash Cup',
            'session_date' => now()->addDays(3)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'reclub_link' => 'not a url',
        ]);

        $response->assertSessionHasErrors('reclub_link');
        $this->assertDatabaseCount('tournaments', 0);
    }

    public function test_tournament_name_is_required(): void
    {
        $admin = User::factory()->admin()->create();
        $court = Court::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/tournaments', [
            'court_id' => $court->id,
            'session_date' => now()->addDays(3)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
        ]);

        $response->assertSessionHasErrors('tournament_name');
        $this->assertDatabaseCount('tournaments', 0);
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        $admin = User::factory()->admin()->create();
        $court = Court::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/tournaments', [
            'court_id' => $court->id,
            'tournament_name' => 'Summer Smash Cup',
            'session_date' => now()->addDays(3)->toDateString(),
            'start_time' => '12:00:00',
            'end_time' => '09:00:00',
        ]);

        $response->assertSessionHasErrors('end_time');
    }

    public function test_scheduling_a_tournament_over_an_existing_booking_is_rejected(): void
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

        $response = $this->actingAs($admin)->post('/admin/tournaments', [
            'court_id' => $court->id,
            'tournament_name' => 'Summer Smash Cup',
            'session_date' => $date,
            'start_time' => '09:30:00',
            'end_time' => '10:30:00',
        ]);

        $response->assertSessionHasErrors('court_id');
        $this->assertDatabaseCount('tournaments', 0);
    }

    public function test_scheduling_a_tournament_over_an_open_play_session_is_rejected(): void
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

        $response = $this->actingAs($admin)->post('/admin/tournaments', [
            'court_id' => $court->id,
            'tournament_name' => 'Summer Smash Cup',
            'session_date' => $date,
            'start_time' => '19:00:00',
            'end_time' => '21:00:00',
        ]);

        $response->assertSessionHasErrors('court_id');
        $this->assertDatabaseCount('tournaments', 0);
    }

    public function test_scheduling_a_tournament_over_a_training_session_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $court = Court::factory()->create();
        $date = now()->addDays(5)->toDateString();

        TrainingSession::factory()->create([
            'court_id' => $court->id,
            'session_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);

        $response = $this->actingAs($admin)->post('/admin/tournaments', [
            'court_id' => $court->id,
            'tournament_name' => 'Summer Smash Cup',
            'session_date' => $date,
            'start_time' => '09:30:00',
            'end_time' => '10:30:00',
        ]);

        $response->assertSessionHasErrors('court_id');
        $this->assertDatabaseCount('tournaments', 0);
    }

    public function test_scheduling_a_tournament_over_another_tournament_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $court = Court::factory()->create();
        $date = now()->addDays(5)->toDateString();

        Tournament::factory()->create([
            'court_id' => $court->id,
            'session_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
        ]);

        $response = $this->actingAs($admin)->post('/admin/tournaments', [
            'court_id' => $court->id,
            'tournament_name' => 'Winter Cup',
            'session_date' => $date,
            'start_time' => '11:00:00',
            'end_time' => '13:00:00',
        ]);

        $response->assertSessionHasErrors('court_id');
        $this->assertDatabaseCount('tournaments', 1);
    }

    public function test_scheduling_an_open_play_session_over_a_tournament_is_rejected(): void
    {
        // Symmetric check: OpenPlaySessionRequest was retrofitted to also
        // check against Tournament rows.
        $admin = User::factory()->admin()->create();
        $court = Court::factory()->create();
        $date = now()->addDays(5)->toDateString();

        Tournament::factory()->create([
            'court_id' => $court->id,
            'session_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
        ]);

        $response = $this->actingAs($admin)->post('/admin/open-play', [
            'court_ids' => [$court->id],
            'session_date' => $date,
            'start_time' => '11:00:00',
            'end_time' => '13:00:00',
        ]);

        $response->assertSessionHasErrors('court_ids');
        $this->assertDatabaseCount('open_play_sessions', 0);
    }

    public function test_scheduling_a_training_session_over_a_tournament_is_rejected(): void
    {
        // Symmetric check: TrainingSessionRequest was retrofitted to also
        // check against Tournament rows.
        $admin = User::factory()->admin()->create();
        $court = Court::factory()->create();
        $date = now()->addDays(5)->toDateString();

        Tournament::factory()->create([
            'court_id' => $court->id,
            'session_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
        ]);

        $response = $this->actingAs($admin)->post('/admin/training-sessions', [
            'court_id' => $court->id,
            'customer_name' => 'Juan Dela Cruz',
            'session_date' => $date,
            'start_time' => '11:00:00',
            'end_time' => '13:00:00',
        ]);

        $response->assertSessionHasErrors('court_id');
        $this->assertDatabaseCount('training_sessions', 0);
    }

    public function test_admin_can_update_a_tournament_without_conflicting_with_itself(): void
    {
        $admin = User::factory()->admin()->create();
        $tournament = Tournament::factory()->create();

        $response = $this->actingAs($admin)->put("/admin/tournaments/{$tournament->id}", [
            'court_id' => $tournament->court_id,
            'tournament_name' => $tournament->tournament_name,
            'session_date' => $tournament->session_date->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '16:00:00',
            'notes' => 'Rescheduled',
        ]);

        $response->assertRedirect('/admin/tournaments');
        $this->assertSame('Rescheduled', $tournament->fresh()->notes);
    }

    public function test_admin_can_delete_a_tournament(): void
    {
        $admin = User::factory()->admin()->create();
        $tournament = Tournament::factory()->create();

        $response = $this->actingAs($admin)->delete("/admin/tournaments/{$tournament->id}");

        $response->assertRedirect('/admin/tournaments');
        $this->assertDatabaseMissing('tournaments', ['id' => $tournament->id]);
    }

    public function test_tournament_shows_as_tournament_on_the_availability_grid(): void
    {
        $court = Court::factory()->create();
        $date = now()->addDays(4)->toDateString();

        BusinessHour::updateOrCreate(
            ['day_of_week' => Carbon::parse($date)->dayOfWeek],
            ['opens_at' => '06:00:00', 'closes_at' => '22:00:00', 'is_closed' => false],
        );

        Tournament::factory()->create([
            'court_id' => $court->id,
            'tournament_name' => 'Summer Smash Cup',
            'session_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'reclub_link' => 'https://reclub.co/clubs/@example',
        ]);

        $day = (new AvailabilityService)->forDate($date);
        $courtAvailability = collect($day['courts'])->first(fn ($ca) => $ca->court->is($court));
        $slot = collect($courtAvailability->slots)->first(fn ($s) => $s->startTime === '10:00:00');

        $this->assertSame('tournament', $slot->status->value);
        $this->assertSame('09:00:00', $slot->tournamentStartTime);
        $this->assertSame('12:00:00', $slot->tournamentEndTime);
        $this->assertSame('Summer Smash Cup', $slot->tournamentName);
        $this->assertSame('https://reclub.co/clubs/@example', $slot->tournamentLink);
    }

    public function test_admin_sees_the_tournament_name_on_the_read_only_schedule(): void
    {
        $court = Court::factory()->create();
        $date = now()->addDays(4)->toDateString();

        BusinessHour::updateOrCreate(
            ['day_of_week' => Carbon::parse($date)->dayOfWeek],
            ['opens_at' => '06:00:00', 'closes_at' => '22:00:00', 'is_closed' => false],
        );

        Tournament::factory()->create([
            'court_id' => $court->id,
            'tournament_name' => 'Summer Smash Cup',
            'session_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get("/manage/schedule?date={$date}")
            ->assertOk()
            ->assertSee('Summer Smash Cup', false);
    }

    public function test_organizer_does_not_see_the_tournament_name_on_the_read_only_schedule(): void
    {
        $court = Court::factory()->create();
        $date = now()->addDays(4)->toDateString();

        BusinessHour::updateOrCreate(
            ['day_of_week' => Carbon::parse($date)->dayOfWeek],
            ['opens_at' => '06:00:00', 'closes_at' => '22:00:00', 'is_closed' => false],
        );

        Tournament::factory()->create([
            'court_id' => $court->id,
            'tournament_name' => 'Summer Smash Cup',
            'session_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
        ]);

        $this->actingAs(User::factory()->organizer()->create())
            ->get("/manage/schedule?date={$date}")
            ->assertOk()
            ->assertDontSee('Summer Smash Cup');
    }

    public function test_tournament_shows_on_the_walkin_grid(): void
    {
        $court = Court::factory()->create();
        $date = now()->addDays(4)->toDateString();

        BusinessHour::updateOrCreate(
            ['day_of_week' => Carbon::parse($date)->dayOfWeek],
            ['opens_at' => '06:00:00', 'closes_at' => '22:00:00', 'is_closed' => false],
        );

        Tournament::factory()->create([
            'court_id' => $court->id,
            'tournament_name' => 'Summer Smash Cup',
            'session_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get("/manage/walk-in?date={$date}")
            ->assertOk()
            ->assertSee('Summer Smash Cup', false);
    }
}
