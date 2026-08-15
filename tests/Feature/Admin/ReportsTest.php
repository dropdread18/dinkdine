<?php

namespace Tests\Feature\Admin;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_and_staff_cannot_access_reports(): void
    {
        $this->actingAs(User::factory()->customer()->create())->get('/manage/reports')->assertForbidden();
        $this->actingAs(User::factory()->staff()->create())->get('/manage/reports')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/manage/reports')->assertRedirect('/login');
    }

    public function test_admin_can_view_the_reports_page(): void
    {
        $booking = Booking::factory()->create();
        Payment::factory()->paid()->create(['booking_id' => $booking->id, 'amount' => 500]);

        $response = $this->actingAs(User::factory()->admin()->create())->get('/manage/reports');

        $response->assertOk();
        $response->assertSee('Revenue');
        $response->assertSee('Court Utilization');
    }

    public function test_week_and_month_range_links_are_selectable(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/manage/reports?range=week')->assertOk();
        $this->actingAs($admin)->get('/manage/reports?range=month')->assertOk();
    }

    public function test_custom_range_accepts_start_and_end_dates(): void
    {
        $booking = Booking::factory()->create();
        Payment::factory()->paid()->create([
            'booking_id' => $booking->id, 'amount' => 777, 'paid_at' => '2026-06-15 10:00:00',
        ]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get('/manage/reports?range=custom&start=2026-06-01&end=2026-06-30');

        $response->assertOk();
        $response->assertSee('777.00');
    }

    public function test_reports_page_shows_a_trailing_seven_day_revenue_chart_regardless_of_selected_range(): void
    {
        $booking = Booking::factory()->create();
        Payment::factory()->paid()->create(['booking_id' => $booking->id, 'amount' => 888, 'paid_at' => now()]);

        $oldBooking = Booking::factory()->create();
        Payment::factory()->paid()->create(['booking_id' => $oldBooking->id, 'amount' => 555, 'paid_at' => now()->subDays(10)]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get('/manage/reports?range=month');

        $response->assertOk();
        $response->assertSee('Revenue — Last 7 Days');
        $response->assertSee('₱888');
        $response->assertDontSee('₱555');
    }

    public function test_bookings_export_returns_a_csv_with_expected_rows(): void
    {
        $booking = Booking::factory()->create(['booking_date' => now()->toDateString()]);

        $response = $this->actingAs(User::factory()->admin()->create())->get('/manage/reports/export/bookings');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringContainsString('PB-'.$booking->id, $content);
        $this->assertStringContainsString($booking->user->email, $content);
    }

    public function test_payments_export_returns_a_csv_with_expected_rows(): void
    {
        $booking = Booking::factory()->create(['booking_date' => now()->toDateString()]);
        Payment::factory()->paid()->create(['booking_id' => $booking->id, 'amount' => 300]);

        $response = $this->actingAs(User::factory()->admin()->create())->get('/manage/reports/export/payments');

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('PB-'.$booking->id, $content);
        $this->assertStringContainsString('300.00', $content);
    }

    public function test_staff_cannot_export_reports(): void
    {
        $this->actingAs(User::factory()->staff()->create())->get('/manage/reports/export/bookings')->assertForbidden();
    }
}
