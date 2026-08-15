<?php

namespace Tests\Feature\Admin;

use App\Models\BusinessHour;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private function validSettingsPayload(array $overrides = []): array
    {
        return array_merge([
            'facility_name' => 'Test Facility',
            'facility_address' => '123 Main St',
            'facility_phone' => '0917-000-0000',
            'facility_email' => 'facility@example.com',
            'currency' => 'PHP',
            'default_booking_duration_minutes' => '60',
            'max_booking_duration_minutes' => '120',
            'min_booking_notice_minutes' => '30',
            'max_advance_booking_days' => '30',
            'cancellation_deadline_hours' => '4',
            'max_simultaneous_bookings_per_customer' => '3',
            'default_court_hourly_rate' => '350',
        ], $overrides);
    }

    public function test_customer_and_staff_cannot_access_settings(): void
    {
        $this->actingAs(User::factory()->customer()->create())->get('/manage/settings')->assertForbidden();
        $this->actingAs(User::factory()->staff()->create())->get('/manage/settings')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/manage/settings')->assertRedirect('/login');
    }

    public function test_admin_can_view_the_settings_page(): void
    {
        Setting::set('facility_name', 'Pickleball Court Booking');
        BusinessHour::factory()->create(['day_of_week' => 0]);

        $response = $this->actingAs(User::factory()->admin()->create())->get('/manage/settings');

        $response->assertOk();
        $response->assertSee('Facility Name');
        $response->assertSee('Business Hours');
    }

    public function test_admin_can_update_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->put('/manage/settings', $this->validSettingsPayload([
            'facility_name' => 'Updated Facility Name',
            'default_court_hourly_rate' => '425.50',
        ]));

        $response->assertRedirect('/manage/settings');
        $this->assertSame('Updated Facility Name', Setting::get('facility_name'));
        $this->assertSame('425.50', Setting::get('default_court_hourly_rate'));
    }

    public function test_max_booking_duration_must_be_at_least_the_default_duration(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->put('/manage/settings', $this->validSettingsPayload([
            'default_booking_duration_minutes' => '120',
            'max_booking_duration_minutes' => '60',
        ]));

        $response->assertSessionHasErrors('max_booking_duration_minutes');
    }

    public function test_admin_can_upload_a_payment_qr_code(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put('/manage/settings', $this->validSettingsPayload([
            'payment_qr_code' => UploadedFile::fake()->image('qr.png'),
        ]));

        $path = Setting::get('payment_qr_code');
        $this->assertNotEmpty($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_uploading_a_new_qr_code_deletes_the_old_one(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put('/manage/settings', $this->validSettingsPayload([
            'payment_qr_code' => UploadedFile::fake()->image('first.png'),
        ]));
        $firstPath = Setting::get('payment_qr_code');

        $this->actingAs($admin)->put('/manage/settings', $this->validSettingsPayload([
            'payment_qr_code' => UploadedFile::fake()->image('second.png'),
        ]));
        $secondPath = Setting::get('payment_qr_code');

        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);
    }

    public function test_admin_can_remove_an_uploaded_qr_code(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put('/manage/settings', $this->validSettingsPayload([
            'payment_qr_code' => UploadedFile::fake()->image('qr.png'),
        ]));
        $path = Setting::get('payment_qr_code');

        $this->actingAs($admin)->put('/manage/settings', $this->validSettingsPayload([
            'remove_payment_qr_code' => '1',
        ]));

        $this->assertSame('', Setting::get('payment_qr_code'));
        Storage::disk('public')->assertMissing($path);
    }

    public function test_settings_without_a_qr_code_change_are_left_untouched(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put('/manage/settings', $this->validSettingsPayload([
            'payment_qr_code' => UploadedFile::fake()->image('qr.png'),
        ]));
        $path = Setting::get('payment_qr_code');

        $this->actingAs($admin)->put('/manage/settings', $this->validSettingsPayload([
            'facility_name' => 'Renamed Facility',
        ]));

        $this->assertSame($path, Setting::get('payment_qr_code'));
        Storage::disk('public')->assertExists($path);
    }

    public function test_staff_cannot_update_settings(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->put('/manage/settings', $this->validSettingsPayload())->assertForbidden();
    }

    public function test_admin_can_update_business_hours(): void
    {
        BusinessHour::factory()->create(['day_of_week' => 0, 'is_closed' => false, 'opens_at' => '06:00:00', 'closes_at' => '22:00:00']);
        $admin = User::factory()->admin()->create();

        $hours = [0 => ['is_closed' => '1']];
        for ($day = 1; $day <= 6; $day++) {
            $hours[$day] = ['opens_at' => '07:00:00', 'closes_at' => '21:00:00'];
        }

        $response = $this->actingAs($admin)->put('/manage/settings/business-hours', ['hours' => $hours]);

        $response->assertRedirect('/manage/settings');

        $sunday = BusinessHour::where('day_of_week', 0)->first();
        $this->assertTrue($sunday->is_closed);
        $this->assertNull($sunday->opens_at);

        $monday = BusinessHour::where('day_of_week', 1)->first();
        $this->assertFalse($monday->is_closed);
        $this->assertSame('07:00:00', $monday->opens_at);
        $this->assertSame('21:00:00', $monday->closes_at);
    }

    public function test_open_day_requires_opening_and_closing_time(): void
    {
        $admin = User::factory()->admin()->create();

        $hours = [];
        for ($day = 0; $day <= 6; $day++) {
            $hours[$day] = ['opens_at' => '', 'closes_at' => ''];
        }

        $response = $this->actingAs($admin)->put('/manage/settings/business-hours', ['hours' => $hours]);

        $response->assertSessionHasErrors('hours.0.opens_at');
    }

    public function test_closing_time_must_be_after_opening_time(): void
    {
        $admin = User::factory()->admin()->create();

        $hours = [];
        for ($day = 0; $day <= 6; $day++) {
            $hours[$day] = ['opens_at' => '20:00:00', 'closes_at' => '10:00:00'];
        }

        $response = $this->actingAs($admin)->put('/manage/settings/business-hours', ['hours' => $hours]);

        $response->assertSessionHasErrors('hours.0.closes_at');
    }
}
