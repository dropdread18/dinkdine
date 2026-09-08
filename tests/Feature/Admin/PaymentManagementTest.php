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

    public function test_customer_cannot_access_payments(): void
    {
        $this->actingAs(User::factory()->customer()->create())->get('/manage/payments')->assertForbidden();
    }

    public function test_staff_can_view_the_payments_list_and_mark_a_payment_paid(): void
    {
        $booking = Booking::factory()->create();
        $payment = Payment::factory()->create(['booking_id' => $booking->id]);
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get('/manage/payments')->assertOk()->assertSee($booking->user->name);

        $response = $this->actingAs($staff)->patch("/manage/payments/{$payment->id}/mark-paid", [
            'method' => 'cash', 'notes' => 'Paid at the counter',
        ]);

        $response->assertRedirect();
        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);
    }

    public function test_staff_cannot_mark_a_payment_failed_or_refund_it(): void
    {
        $unpaidBooking = Booking::factory()->create();
        $unpaidPayment = Payment::factory()->create(['booking_id' => $unpaidBooking->id]);
        $paidBooking = Booking::factory()->create();
        $paidPayment = Payment::factory()->paid()->create(['booking_id' => $paidBooking->id]);
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->patch("/manage/payments/{$unpaidPayment->id}/mark-failed", ['reason' => 'test'])->assertForbidden();
        $this->actingAs($staff)->patch("/manage/payments/{$paidPayment->id}/refund")->assertForbidden();
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

    public function test_admin_sees_all_payment_actions_but_staff_only_sees_mark_paid(): void
    {
        $booking = Booking::factory()->create();
        Payment::factory()->create(['booking_id' => $booking->id]);

        $adminResponse = $this->actingAs(User::factory()->admin()->create())->get("/bookings/{$booking->id}");
        $adminResponse->assertSee('Mark Paid')->assertSee('Mark Failed');

        $staffResponse = $this->actingAs(User::factory()->staff()->create())->get("/bookings/{$booking->id}");
        $staffResponse->assertSee('Mark Paid')->assertDontSee('Mark Failed');
    }

    public function test_customer_does_not_see_payment_actions_on_the_booking_detail_page(): void
    {
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create(['user_id' => $customer->id]);
        Payment::factory()->create(['booking_id' => $booking->id]);

        $this->actingAs($customer)->get("/bookings/{$booking->id}")->assertDontSee('Mark Paid');
    }

    public function test_payments_confirmed_together_are_grouped_and_show_a_combined_total(): void
    {
        $user = User::factory()->customer()->create(['name' => 'Juan Dela Cruz']);
        $bookingA = Booking::factory()->create(['user_id' => $user->id]);
        $bookingB = Booking::factory()->create(['user_id' => $user->id]);
        Payment::factory()->create(['booking_id' => $bookingA->id, 'amount' => 265, 'reference_number' => 'GCASH-SHARED-1']);
        Payment::factory()->create(['booking_id' => $bookingB->id, 'amount' => 265, 'reference_number' => 'GCASH-SHARED-1']);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/manage/payments')
            ->assertOk()
            ->assertSee('2 bookings, same payment')
            ->assertSee('₱530.00');
    }

    public function test_payments_with_different_reference_numbers_are_not_grouped(): void
    {
        $user = User::factory()->customer()->create();
        $bookingA = Booking::factory()->create(['user_id' => $user->id]);
        $bookingB = Booking::factory()->create(['user_id' => $user->id]);
        Payment::factory()->create(['booking_id' => $bookingA->id, 'reference_number' => 'GCASH-AAA']);
        Payment::factory()->create(['booking_id' => $bookingB->id, 'reference_number' => 'GCASH-BBB']);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/manage/payments')
            ->assertOk()
            ->assertDontSee('bookings, same payment');
    }

    public function test_staff_can_bulk_mark_multiple_payments_paid_in_one_submission(): void
    {
        $bookingA = Booking::factory()->create();
        $bookingB = Booking::factory()->create();
        $paymentA = Payment::factory()->create(['booking_id' => $bookingA->id]);
        $paymentB = Payment::factory()->create(['booking_id' => $bookingB->id]);

        $response = $this->actingAs(User::factory()->staff()->create())
            ->patch('/manage/payments/bulk-mark-paid', [
                'payment_ids' => [$paymentA->id, $paymentB->id],
                'method' => 'gcash',
            ]);

        $response->assertRedirect();
        $this->assertSame(PaymentStatus::Paid, $paymentA->fresh()->status);
        $this->assertSame(PaymentStatus::Paid, $paymentB->fresh()->status);
    }

    public function test_bulk_mark_paid_preserves_each_payments_own_reference_number(): void
    {
        $bookingA = Booking::factory()->create();
        $bookingB = Booking::factory()->create();
        $paymentA = Payment::factory()->create(['booking_id' => $bookingA->id, 'reference_number' => 'GCASH-SHARED-1']);
        $paymentB = Payment::factory()->create(['booking_id' => $bookingB->id, 'reference_number' => 'GCASH-SHARED-1']);

        $this->actingAs(User::factory()->admin()->create())->patch('/manage/payments/bulk-mark-paid', [
            'payment_ids' => [$paymentA->id, $paymentB->id],
            'method' => 'gcash',
        ]);

        $this->assertSame('GCASH-SHARED-1', $paymentA->fresh()->reference_number);
        $this->assertSame('GCASH-SHARED-1', $paymentB->fresh()->reference_number);
    }

    public function test_bulk_mark_paid_skips_an_already_paid_payment_without_failing_the_rest(): void
    {
        $alreadyPaidBooking = Booking::factory()->create();
        $alreadyPaid = Payment::factory()->paid()->create(['booking_id' => $alreadyPaidBooking->id]);
        $unpaidBooking = Booking::factory()->create();
        $unpaid = Payment::factory()->create(['booking_id' => $unpaidBooking->id]);

        $response = $this->actingAs(User::factory()->admin()->create())->patch('/manage/payments/bulk-mark-paid', [
            'payment_ids' => [$alreadyPaid->id, $unpaid->id],
            'method' => 'cash',
        ]);

        $response->assertSessionHas('status');
        $this->assertSame(PaymentStatus::Paid, $unpaid->fresh()->status);
    }

    public function test_only_henris_account_can_bulk_delete_payments(): void
    {
        $booking = Booking::factory()->create();
        $payment = Payment::factory()->create(['booking_id' => $booking->id]);
        $otherAdmin = User::factory()->admin()->create(['email' => 'someoneelse@gmail.com']);

        $this->actingAs($otherAdmin)->delete('/manage/payments/bulk-delete', [
            'payment_ids' => [$payment->id],
        ])->assertNotFound();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id]);
    }

    public function test_staff_cannot_bulk_delete_payments(): void
    {
        $booking = Booking::factory()->create();
        $payment = Payment::factory()->create(['booking_id' => $booking->id]);

        $this->actingAs(User::factory()->staff()->create())->delete('/manage/payments/bulk-delete', [
            'payment_ids' => [$payment->id],
        ])->assertForbidden();
    }

    public function test_henri_can_permanently_delete_bookings_and_their_payments_in_bulk(): void
    {
        $bookingA = Booking::factory()->create();
        $bookingB = Booking::factory()->create();
        $paymentA = Payment::factory()->create(['booking_id' => $bookingA->id]);
        $paymentB = Payment::factory()->create(['booking_id' => $bookingB->id]);
        $henri = User::factory()->admin()->create(['email' => 'hjbalbiran@gmail.com']);

        $response = $this->actingAs($henri)->delete('/manage/payments/bulk-delete', [
            'payment_ids' => [$paymentA->id, $paymentB->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('bookings', ['id' => $bookingA->id]);
        $this->assertDatabaseMissing('bookings', ['id' => $bookingB->id]);
        $this->assertDatabaseMissing('payments', ['id' => $paymentA->id]);
        $this->assertDatabaseMissing('payments', ['id' => $paymentB->id]);
    }
}
