<?php

namespace Tests\Feature\Admin;

use App\Enums\CourtStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourtManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_and_staff_cannot_access_court_management(): void
    {
        $this->actingAs(User::factory()->customer()->create())->get('/admin/courts')->assertForbidden();
        $this->actingAs(User::factory()->staff()->create())->get('/admin/courts')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/courts')->assertRedirect('/login');
    }

    public function test_admin_can_view_the_courts_list(): void
    {
        $admin = User::factory()->admin()->create();
        Court::factory()->create(['name' => 'Court 7']);

        $this->actingAs($admin)->get('/admin/courts')->assertOk()->assertSee('Court 7');
    }

    public function test_admin_can_create_a_court(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/courts', [
            'name' => 'Court 9',
            'court_number' => 9,
            'hourly_rate' => 250,
            'evening_hourly_rate' => 350,
            'status' => CourtStatus::Active->value,
        ]);

        $response->assertRedirect(route('admin.courts.index'));
        $this->assertDatabaseHas('courts', ['name' => 'Court 9', 'court_number' => 9, 'evening_hourly_rate' => 350]);
    }

    public function test_court_number_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        Court::factory()->create(['court_number' => 5]);

        $response = $this->actingAs($admin)->post('/admin/courts', [
            'name' => 'Duplicate',
            'court_number' => 5,
            'hourly_rate' => 300,
            'evening_hourly_rate' => 350,
            'status' => CourtStatus::Active->value,
        ]);

        $response->assertSessionHasErrors('court_number');
        $this->assertSame(1, Court::where('court_number', 5)->count());
    }

    public function test_admin_can_update_a_court(): void
    {
        $admin = User::factory()->admin()->create();
        $court = Court::factory()->create(['name' => 'Old Name', 'status' => CourtStatus::Active]);

        $response = $this->actingAs($admin)->put("/admin/courts/{$court->id}", [
            'name' => 'New Name',
            'court_number' => $court->court_number,
            'hourly_rate' => $court->hourly_rate,
            'evening_hourly_rate' => $court->evening_hourly_rate,
            'status' => CourtStatus::Maintenance->value,
        ]);

        $response->assertRedirect(route('admin.courts.index'));
        $this->assertSame('New Name', $court->fresh()->name);
        $this->assertSame(CourtStatus::Maintenance, $court->fresh()->status);
    }

    public function test_editing_a_court_can_keep_its_own_court_number(): void
    {
        $admin = User::factory()->admin()->create();
        $court = Court::factory()->create(['court_number' => 3]);

        $response = $this->actingAs($admin)->put("/admin/courts/{$court->id}", [
            'name' => $court->name,
            'court_number' => 3,
            'hourly_rate' => $court->hourly_rate,
            'evening_hourly_rate' => $court->evening_hourly_rate,
            'status' => CourtStatus::Active->value,
        ]);

        $response->assertSessionHasNoErrors();
    }

    public function test_admin_can_delete_a_court_with_no_bookings(): void
    {
        $admin = User::factory()->admin()->create();
        $court = Court::factory()->create();

        $this->actingAs($admin)->delete("/admin/courts/{$court->id}")->assertRedirect(route('admin.courts.index'));
        $this->assertDatabaseMissing('courts', ['id' => $court->id]);
    }

    public function test_admin_cannot_delete_a_court_that_has_bookings(): void
    {
        $admin = User::factory()->admin()->create();
        $court = Court::factory()->create();
        Booking::factory()->create(['court_id' => $court->id]);

        $response = $this->actingAs($admin)->delete("/admin/courts/{$court->id}");

        $response->assertSessionHasErrors('court');
        $this->assertDatabaseHas('courts', ['id' => $court->id]);
    }

    public function test_admin_can_upload_a_court_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/courts', [
            'name' => 'Court With Image',
            'court_number' => 11,
            'hourly_rate' => 300,
            'evening_hourly_rate' => 350,
            'status' => CourtStatus::Active->value,
            'image' => UploadedFile::fake()->image('court.jpg'),
        ]);

        $court = Court::where('court_number', 11)->firstOrFail();
        $this->assertNotNull($court->image);
        Storage::disk('public')->assertExists($court->image);
    }
}
