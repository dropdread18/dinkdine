<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BusinessHour;
use App\Models\Court;
use App\Models\OpenPlaySession;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TrainingSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_and_staff_cannot_access_training_sessions(): void
    {
        $this->actingAs(User::factory()->customer()->create())->get('/admin/training-sessions')->assertForbidden();
        $this->actingAs(User::factory()->staff()->create())->get('/admin/training-sessions')->assertForbidden();
    }

    public function test_organizer_cannot_access_training_sessions(): void
    {
        // Unlike Open Play, Training Session is admin-only - Organizer
        // never needs it (owner request: it's an internal coaching/drill
        // booking tied to a named customer, not a public event).
        $this->actingAs(User::factory()->organizer()->create())->get('/admin/training-sessions')->assertForbidden();
        $this->actingAs(User::factory()->organizer()->create())->get('/admin/training-sessions/create')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/training-sessions')->assertRedirect('/login');
    }

    public function test_admin_can_view_the_training_session_list(): void
    {
        $court = Court::factory()->create(['name' => 'Court 9']);
        TrainingSession::factory()->create(['court_id' => $court->id, 'customer_name' => 'Maria Santos']);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/training-sessions')
            ->assertOk()
            ->assertSee('Court 9')
            ->assertSee('Maria Santos');
    }

    public function test_admin_can_schedule_a_training_session(): void
    {
        $admin = User::factory()->admin()->create();
        $court = Court::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/training-sessions', [
            'court_id' => $court->id,
            'customer_name' => 'Juan Dela Cruz',
            'session_date' => now()->addDays(3)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'notes' => 'Backhand drills',
        ]);

        $response->assertRedirect('/admin/training-sessions');
        $this->assertDatabaseHas('training_sessions', [
            'court_id' => $court->id,
            'customer_name' => 'Juan Dela Cruz',
            'notes' => 'Backhand drills',
            'created_by' => $admin->id,
        ]);
    }

    public function test_admin_can_set_a_reclub_link_when_scheduling_a_training_session(): void
    {
        $admin = User::factory()->admin()->create();
        $court = Court::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/training-sessions', [
            'court_id' => $court->id,
            'customer_name' => 'Juan Dela Cruz',
            'session_date' => now()->addDays(3)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'reclub_link' => 'https://reclub.co/clubs/@example',
        ]);

        $response->assertRedirect('/admin/training-sessions');
        $this->assertDatabaseHas('training_sessions', [
            'court_id' => $court->id,
            'reclub_link' => 'https://reclub.co/clubs/@example',
        ]);
    }

    public function test_reclub_link_must_be_a_valid_url(): void
    {
        $admin = User::factory()->admin()->create();
        $court = Court::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/training-sessions', [
            'court_id' => $court->id,
            'customer_name' => 'Juan Dela Cruz',
            'session_date' => now()->addDays(3)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'reclub_link' => 'not a url',
        ]);

        $response->assertSessionHasErrors('reclub_link');
        $this->assertDatabaseCount('training_sessions', 0);
    }

    public function test_customer_name_is_required(): void
    {
        $admin = User::factory()->admin()->create();
        $court = Court::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/training-sessions', [
            'court_id' => $court->id,
            'session_date' => now()->addDays(3)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);

        $response->assertSessionHasErrors('customer_name');
        $this->assertDatabaseCount('training_sessions', 0);
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        $admin = User::factory()->admin()->create();
        $court = Court::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/training-sessions', [
            'court_id' => $court->id,
            'customer_name' => 'Juan Dela Cruz',
            'session_date' => now()->addDays(3)->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '09:00:00',
        ]);

        $response->assertSessionHasErrors('end_time');
    }

    public function test_scheduling_a_training_session_over_an_existing_booking_is_rejected(): void
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

        $response = $this->actingAs($admin)->post('/admin/training-sessions', [
            'court_id' => $court->id,
            'customer_name' => 'Juan Dela Cruz',
            'session_date' => $date,
            'start_time' => '09:30:00',
            'end_time' => '10:30:00',
        ]);

        $response->assertSessionHasErrors('court_id');
        $this->assertDatabaseCount('training_sessions', 0);
    }

    public function test_scheduling_a_training_session_over_an_open_play_session_is_rejected(): void
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

        $response = $this->actingAs($admin)->post('/admin/training-sessions', [
            'court_id' => $court->id,
            'customer_name' => 'Juan Dela Cruz',
            'session_date' => $date,
            'start_time' => '19:00:00',
            'end_time' => '21:00:00',
        ]);

        $response->assertSessionHasErrors('court_id');
        $this->assertDatabaseCount('training_sessions', 0);
    }

    public function test_scheduling_a_training_session_over_another_training_session_is_rejected(): void
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

        $response = $this->actingAs($admin)->post('/admin/training-sessions', [
            'court_id' => $court->id,
            'customer_name' => 'Juan Dela Cruz',
            'session_date' => $date,
            'start_time' => '09:30:00',
            'end_time' => '10:30:00',
        ]);

        $response->assertSessionHasErrors('court_id');
        $this->assertDatabaseCount('training_sessions', 1);
    }

    public function test_scheduling_an_open_play_session_over_a_training_session_is_rejected(): void
    {
        // Symmetric check: OpenPlaySessionRequest was retrofitted to also
        // check against TrainingSession rows, so the conflict is caught
        // no matter which one is created first.
        $admin = User::factory()->admin()->create();
        $court = Court::factory()->create();
        $date = now()->addDays(5)->toDateString();

        TrainingSession::factory()->create([
            'court_id' => $court->id,
            'session_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);

        $response = $this->actingAs($admin)->post('/admin/open-play', [
            'court_ids' => [$court->id],
            'session_date' => $date,
            'start_time' => '09:30:00',
            'end_time' => '11:00:00',
        ]);

        $response->assertSessionHasErrors('court_ids');
        $this->assertDatabaseCount('open_play_sessions', 0);
    }

    public function test_admin_can_update_a_training_session_without_conflicting_with_itself(): void
    {
        $admin = User::factory()->admin()->create();
        $session = TrainingSession::factory()->create();

        $response = $this->actingAs($admin)->put("/admin/training-sessions/{$session->id}", [
            'court_id' => $session->court_id,
            'customer_name' => $session->customer_name,
            'session_date' => $session->session_date->toDateString(),
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'notes' => 'Rescheduled',
        ]);

        $response->assertRedirect('/admin/training-sessions');
        $this->assertSame('Rescheduled', $session->fresh()->notes);
    }

    public function test_admin_can_delete_a_training_session(): void
    {
        $admin = User::factory()->admin()->create();
        $session = TrainingSession::factory()->create();

        $response = $this->actingAs($admin)->delete("/admin/training-sessions/{$session->id}");

        $response->assertRedirect('/admin/training-sessions');
        $this->assertDatabaseMissing('training_sessions', ['id' => $session->id]);
    }

    public function test_training_session_shows_as_training_session_on_the_availability_grid(): void
    {
        $court = Court::factory()->create();
        $date = now()->addDays(4)->toDateString();

        BusinessHour::updateOrCreate(
            ['day_of_week' => Carbon::parse($date)->dayOfWeek],
            ['opens_at' => '06:00:00', 'closes_at' => '22:00:00', 'is_closed' => false],
        );

        TrainingSession::factory()->create([
            'court_id' => $court->id,
            'customer_name' => 'Maria Santos',
            'session_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);

        $day = (new AvailabilityService)->forDate($date);
        $courtAvailability = collect($day['courts'])->first(fn ($ca) => $ca->court->is($court));
        $slot = collect($courtAvailability->slots)->first(fn ($s) => $s->startTime === '09:00:00');

        $this->assertSame('training_session', $slot->status->value);
        $this->assertSame('09:00:00', $slot->trainingSessionStartTime);
        $this->assertSame('10:00:00', $slot->trainingSessionEndTime);
        $this->assertSame('Maria Santos', $slot->trainingCustomerName);
    }

    public function test_training_session_slot_carries_its_reclub_link(): void
    {
        $court = Court::factory()->create();
        $date = now()->addDays(4)->toDateString();

        BusinessHour::updateOrCreate(
            ['day_of_week' => Carbon::parse($date)->dayOfWeek],
            ['opens_at' => '06:00:00', 'closes_at' => '22:00:00', 'is_closed' => false],
        );

        TrainingSession::factory()->create([
            'court_id' => $court->id,
            'session_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'reclub_link' => 'https://reclub.co/clubs/@example',
        ]);

        $day = (new AvailabilityService)->forDate($date);
        $courtAvailability = collect($day['courts'])->first(fn ($ca) => $ca->court->is($court));
        $slot = collect($courtAvailability->slots)->first(fn ($s) => $s->startTime === '09:00:00');

        $this->assertSame('https://reclub.co/clubs/@example', $slot->trainingSessionLink);
    }

    public function test_admin_sees_the_customer_name_on_the_read_only_schedule(): void
    {
        $court = Court::factory()->create();
        $date = now()->addDays(4)->toDateString();

        BusinessHour::updateOrCreate(
            ['day_of_week' => Carbon::parse($date)->dayOfWeek],
            ['opens_at' => '06:00:00', 'closes_at' => '22:00:00', 'is_closed' => false],
        );

        TrainingSession::factory()->create([
            'court_id' => $court->id,
            'customer_name' => 'Maria Santos',
            'session_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get("/manage/schedule?date={$date}")
            ->assertOk()
            ->assertSee('Maria Santos', false);
    }

    public function test_organizer_does_not_see_the_customer_name_on_the_read_only_schedule(): void
    {
        // DEC-023: Organizer must never see customer-adjacent info. It
        // reaches /manage/schedule (unlike /admin/training-sessions,
        // which it's forbidden from entirely) so the grid itself must
        // withhold the name even though the Training Session slot still
        // shows there as booked-out time.
        $court = Court::factory()->create();
        $date = now()->addDays(4)->toDateString();

        BusinessHour::updateOrCreate(
            ['day_of_week' => Carbon::parse($date)->dayOfWeek],
            ['opens_at' => '06:00:00', 'closes_at' => '22:00:00', 'is_closed' => false],
        );

        TrainingSession::factory()->create([
            'court_id' => $court->id,
            'customer_name' => 'Maria Santos',
            'session_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);

        $this->actingAs(User::factory()->organizer()->create())
            ->get("/manage/schedule?date={$date}")
            ->assertOk()
            ->assertDontSee('Maria Santos');
    }

    public function test_admin_sees_the_reclub_link_on_the_read_only_schedule(): void
    {
        $court = Court::factory()->create();
        $date = now()->addDays(4)->toDateString();

        BusinessHour::updateOrCreate(
            ['day_of_week' => Carbon::parse($date)->dayOfWeek],
            ['opens_at' => '06:00:00', 'closes_at' => '22:00:00', 'is_closed' => false],
        );

        TrainingSession::factory()->create([
            'court_id' => $court->id,
            'session_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'reclub_link' => 'https://reclub.co/clubs/@example',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get("/manage/schedule?date={$date}")
            ->assertOk()
            ->assertSee('https://reclub.co/clubs/@example', false);
    }

    public function test_organizer_does_not_see_the_reclub_link_on_the_read_only_schedule(): void
    {
        // Same DEC-023-style gate as the customer name above - the link
        // is only ever meant for admin/staff, so it's gated the same way.
        $court = Court::factory()->create();
        $date = now()->addDays(4)->toDateString();

        BusinessHour::updateOrCreate(
            ['day_of_week' => Carbon::parse($date)->dayOfWeek],
            ['opens_at' => '06:00:00', 'closes_at' => '22:00:00', 'is_closed' => false],
        );

        TrainingSession::factory()->create([
            'court_id' => $court->id,
            'session_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'reclub_link' => 'https://reclub.co/clubs/@example',
        ]);

        $this->actingAs(User::factory()->organizer()->create())
            ->get("/manage/schedule?date={$date}")
            ->assertOk()
            ->assertDontSee('https://reclub.co/clubs/@example');
    }

    public function test_training_session_shows_on_the_walkin_grid(): void
    {
        $court = Court::factory()->create();
        $date = now()->addDays(4)->toDateString();

        BusinessHour::updateOrCreate(
            ['day_of_week' => Carbon::parse($date)->dayOfWeek],
            ['opens_at' => '06:00:00', 'closes_at' => '22:00:00', 'is_closed' => false],
        );

        TrainingSession::factory()->create([
            'court_id' => $court->id,
            'customer_name' => 'Maria Santos',
            'session_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get("/manage/walk-in?date={$date}")
            ->assertOk()
            ->assertSee('Maria Santos', false);
    }
}
