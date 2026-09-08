<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\PaymentActionException;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaymentController extends Controller
{
    /**
     * Only Henri's own account can permanently delete payment/booking
     * records - see bulkDelete() below. Same email-gating pattern as
     * StoreMenuController; the other admin account on this system
     * (randerexbisquera11@gmail.com) must not have this either.
     */
    private const DELETE_ALLOWED_EMAIL = 'hjbalbiran@gmail.com';

    public function index(Request $request): View
    {
        $query = Payment::query()->with(['booking.court', 'booking.user']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($q = $request->query('q')) {
            $idCandidate = preg_replace('/^PB-/i', '', trim($q));

            $query->where(function ($sub) use ($q, $idCandidate) {
                $sub->where('booking_id', $idCandidate)
                    ->orWhereHas('booking.user', function ($userQuery) use ($q) {
                        $userQuery->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%")
                            ->orWhere('phone', 'like', "%{$q}%");
                    });
            });
        }

        $payments = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        // Every payment confirmed together in one checkout submission shares
        // the exact same reference_number (BookingService::confirmWithReference()
        // sets it once across the whole batch - see that method) - grouping
        // on (customer, reference_number) is what lets a 2-hour booking that
        // became 2 separate Payment rows be approved as a single unit
        // instead of one row at a time. A payment with no reference number
        // yet (still unpaid, nothing submitted) just gets its own group.
        $groups = $payments->getCollection()->groupBy(
            fn (Payment $payment) => $payment->reference_number
                ? $payment->booking->user_id.'|'.$payment->reference_number
                : 'single-'.$payment->id
        );

        return view('admin.payments.index', [
            'payments' => $payments,
            'groups' => $groups,
            'canBulkDelete' => $request->user()?->email === self::DELETE_ALLOWED_EMAIL,
        ]);
    }

    public function markPaid(Request $request, Payment $payment, PaymentService $paymentService): RedirectResponse
    {
        $data = $request->validate([
            'method' => ['required', 'string', 'max:255'],
            // Required specifically for GCash - cash/bank transfer/other
            // don't have a GCash-style reference number to confirm against.
            'reference_number' => ['required_if:method,gcash', 'nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $paymentService->markPaid($payment, $data['method'], $data['notes'] ?? null, $data['reference_number'] ?? null);
        } catch (PaymentActionException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        return back()->with('status', 'Payment marked as paid.');
    }

    public function markFailed(Request $request, Payment $payment, PaymentService $paymentService): RedirectResponse
    {
        try {
            $paymentService->markFailed($payment, $request->input('reason'));
        } catch (PaymentActionException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        return back()->with('status', 'Payment marked as failed.');
    }

    public function refund(Request $request, Payment $payment, PaymentService $paymentService): RedirectResponse
    {
        $partial = $request->boolean('partial');

        try {
            $paymentService->refund($payment, $partial, $request->input('reason'));
        } catch (PaymentActionException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        return back()->with('status', $partial ? 'Payment partially refunded.' : 'Payment refunded.');
    }

    /**
     * Marks every selected payment paid in one submission, sharing the same
     * method/notes across all of them - each payment keeps its own
     * already-recorded reference number (PaymentService::markPaid() only
     * overwrites it when one is explicitly passed, which this deliberately
     * doesn't do). Skips any row that's no longer in a markable state
     * (already paid, etc.) instead of failing the whole batch over one
     * stale row - a page the admin hasn't refreshed yet shouldn't block
     * everything else they selected.
     */
    public function bulkMarkPaid(Request $request, PaymentService $paymentService): RedirectResponse
    {
        $data = $request->validate([
            'payment_ids' => ['required', 'array', 'min:1'],
            'payment_ids.*' => ['integer', 'exists:payments,id'],
            'method' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $marked = 0;
        $skipped = 0;

        foreach (Payment::whereIn('id', $data['payment_ids'])->get() as $payment) {
            try {
                $paymentService->markPaid($payment, $data['method'], $data['notes'] ?? null);
                $marked++;
            } catch (PaymentActionException) {
                $skipped++;
            }
        }

        $status = "{$marked} payment".($marked === 1 ? '' : 's').' marked as paid.';
        if ($skipped > 0) {
            $status .= " {$skipped} skipped (already settled).";
        }

        return back()->with('status', $status);
    }

    /**
     * Permanently deletes the selected payments' bookings (the payment rows
     * cascade-delete with them - see the payments table's
     * cascadeOnDelete()). Gone from reports and history entirely, unlike
     * markFailed - only Henri's own account can do this, checked here
     * rather than just at the route level so it fails the same way an
     * unknown route would (404, not a 403 that confirms the feature
     * exists) for anyone else, including the other admin on this system.
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        if ($request->user()?->email !== self::DELETE_ALLOWED_EMAIL) {
            throw new HttpException(404);
        }

        $data = $request->validate([
            'payment_ids' => ['required', 'array', 'min:1'],
            'payment_ids.*' => ['integer', 'exists:payments,id'],
        ]);

        $bookingIds = Payment::whereIn('id', $data['payment_ids'])->pluck('booking_id');
        $deleted = Booking::whereIn('id', $bookingIds)->delete();

        return back()->with('status', "{$deleted} booking".($deleted === 1 ? '' : 's').' permanently deleted.');
    }
}
