<?php

namespace Tests\Feature\Admin;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_and_staff_cannot_access_payments(): void
    {
        $this->actingAs(User::factory()->customer()->create())->get('/manage/payments')->assertForbidden();
        $this->actingAs(User::factory()->staff()->create())->get('/manage/payments')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/manage/payments')->assertRedirect('/login');
    }

    public function test_admin_can_view_the_payments_list(): void
    {
        $booking = Booking::factory()->create();
        $payment = Payment::factory()->create(['booking_id' => $booking->id]);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/manage/payments')
            ->assertOk()
            ->assertSee($booking->user->name)
            ->assertSee('PB-'.$booking->id);
    }

    public function test_payments_can_be_filtered_by_status(): void
    {
        $paidBooking = Booking::factory()->create();
        $paid = Payment::factory()->paid()->create(['booking_id' => $paidBooking->id]);
        $unpaidBooking = Booking::factory()->create();
        Payment::factory()->create(['booking_id' => $unpaidBooking->id]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get('/manage/payments?status=paid');

        $response->assertSee($paidBooking->user->name);
        $response->assertDontSee($unpaidBooking->user->name);
    }

    public function test_payments_can_be_searched_by_booking_reference(): void
    {
        $target = Booking::factory()->create();
        Payment::factory()->create(['booking_id' => $target->id]);
        $other = Booking::factory()->create();
        Payment::factory()->create(['booking_id' => $other->id]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get('/manage/payments?q=PB-'.$target->id);

        $response->assertSee($target->user->name);
        $response->assertDontSee($other->user->name);
    }

    public function test_admin_can_mark_a_payment_paid(): void
    {
        $booking = Booking::factory()->create();
        $payment = Payment::factory()->create(['booking_id' => $booking->id]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->patch("/manage/payments/{$payment->id}/mark-paid", [
                'method' => 'gcash', 'reference_number' => 'GCASH-REF-555', 'notes' => 'Confirmed in person',
            ]);

        $response->assertRedirect();
        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);
        $this->assertSame(PaymentStatus::Paid, $booking->fresh()->payment_status);
        $this->assertSame('GCASH-REF-555', $payment->fresh()->reference_number);
    }

    public function test_gcash_payment_requires_a_reference_number(): void
    {
        $booking = Booking::factory()->create();
        $payment = Payment::factory()->create(['booking_id' => $booking->id]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->patch("/manage/payments/{$payment->id}/mark-paid", ['method' => 'gcash']);

        $response->assertSessionHasErrors('reference_number');
        $this->assertSame(PaymentStatus::Unpaid, $payment->fresh()->status);
    }

    public function test_cash_payment_does_not_require_a_reference_number(): void
    {
        $booking = Booking::factory()->create();
        $payment = Payment::factory()->create(['booking_id' => $booking->id]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->patch("/manage/payments/{$payment->id}/mark-paid", ['method' => 'cash']);

        $response->assertRedirect();
        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);
    }

    public function test_marking_an_already_paid_payment_paid_again_fails_gracefully(): void
    {
        $booking = Booking::factory()->create();
        $payment = Payment::factory()->paid()->create(['booking_id' => $booking->id]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->patch("/manage/payments/{$payment->id}/mark-paid", ['method' => 'cash']);

        $response->assertSessionHasErrors('payment');
    }

    public function test_admin_can_mark_a_payment_failed(): void
    {
        $booking = Booking::factory()->create();
        $payment = Payment::factory()->create(['booking_id' => $booking->id]);

        $this->actingAs(User::factory()->admin()->create())
            ->patch("/manage/payments/{$payment->id}/mark-failed", ['reason' => 'Card declined']);

        $this->assertSame(PaymentStatus::Failed, $payment->fresh()->status);
    }

    public function test_admin_can_fully_refund_a_paid_payment(): void
    {
        $booking = Booking::factory()->create();
        $payment = Payment::factory()->paid()->create(['booking_id' => $booking->id]);

        $this->actingAs(User::factory()->admin()->create())
            ->patch("/manage/payments/{$payment->id}/refund", ['reason' => 'Customer request']);

        $this->assertSame(PaymentStatus::Refunded, $payment->fresh()->status);
    }

    public function test_admin_can_partially_refund_a_paid_payment(): void
    {
        $booking = Booking::factory()->create();
        $payment = Payment::factory()->paid()->create(['booking_id' => $booking->id]);

        $this->actingAs(User::factory()->admin()->create())
            ->patch("/manage/payments/{$payment->id}/refund", ['partial' => '1']);

        $this->assertSame(PaymentStatus::PartiallyRefunded, $payment->fresh()->status);
    }

    public function test_refunding_an_unpaid_payment_fails_gracefully(): void
    {
        $booking = Booking::factory()->create();
        $payment = Payment::factory()->create(['booking_id' => $booking->id]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->patch("/manage/payments/{$payment->id}/refund");

        $response->assertSessionHasErrors('payment');
    }

    public function test_only_admin_sees_payment_actions_on_the_booking_detail_page(): void
    {
        $booking = Booking::factory()->create();
        Payment::factory()->create(['booking_id' => $booking->id]);

        $adminResponse = $this->actingAs(User::factory()->admin()->create())->get("/bookings/{$booking->id}");
        $adminResponse->assertSee('Mark Paid');

        $staffResponse = $this->actingAs(User::factory()->staff()->create())->get("/bookings/{$booking->id}");
        $staffResponse->assertDontSee('Mark Paid');
    }
}
