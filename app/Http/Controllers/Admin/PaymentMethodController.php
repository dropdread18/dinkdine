<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentMethodRequest;
use App\Models\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PaymentMethodController extends Controller
{
    public function index(): View
    {
        return view('admin.payment-methods.index', [
            'paymentMethods' => PaymentMethod::orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.payment-methods.create');
    }

    public function store(PaymentMethodRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('qr_code');
        // Unlike update() below, a brand new payment method should default
        // to active even if the request omits is_active entirely (e.g. the
        // create form's checkbox starts checked, or an API call just
        // doesn't mention it) - only an explicit false should opt out.
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;
        $data['qr_code_path'] = $request->file('qr_code')->store('payment-methods', 'public');

        PaymentMethod::create($data);

        return redirect()->route('admin.payment-methods.index')->with('status', 'Payment method added.');
    }

    public function edit(PaymentMethod $paymentMethod): View
    {
        return view('admin.payment-methods.edit', ['paymentMethod' => $paymentMethod]);
    }

    public function update(PaymentMethodRequest $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $data = $request->safe()->except('qr_code');
        // Editing an existing method: an unchecked box is genuinely absent
        // from the request (not sent as false), and that absence means
        // "turn it off" here - unlike store() above, there's no sensible
        // default to fall back on once the admin is editing a real record.
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('qr_code')) {
            Storage::disk('public')->delete($paymentMethod->qr_code_path);
            $data['qr_code_path'] = $request->file('qr_code')->store('payment-methods', 'public');
        }

        $paymentMethod->update($data);

        return redirect()->route('admin.payment-methods.index')->with('status', 'Payment method updated.');
    }

    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        Storage::disk('public')->delete($paymentMethod->qr_code_path);
        $paymentMethod->delete();

        return redirect()->route('admin.payment-methods.index')->with('status', 'Payment method removed.');
    }
}
