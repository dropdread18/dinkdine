<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\PaymentActionException;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
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

        return view('admin.payments.index', ['payments' => $payments]);
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
}
