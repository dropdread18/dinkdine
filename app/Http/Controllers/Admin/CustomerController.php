<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCustomerRequest;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->where('role', UserRole::Customer);

        if ($q = $request->query('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        $customers = $query->withCount('bookings')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.customers.index', ['customers' => $customers, 'q' => $q ?? '']);
    }

    public function create(): View
    {
        return view('admin.customers.create');
    }

    public function store(CreateCustomerRequest $request): RedirectResponse
    {
        $customer = User::create([
            ...$request->validated(),
            // Raw string, not Hash::make() - User::password has a 'hashed'
            // cast that hashes on save; same pattern as walk-in/guest
            // customer creation. Admin-created customers get in via the
            // existing password-reset flow, same as any other customer.
            'password' => Str::random(32),
            'role' => UserRole::Customer,
        ]);

        return redirect()->route('admin.customers.show', $customer)->with('status', 'Customer created.');
    }

    public function show(User $customer): View
    {
        abort_unless($customer->role === UserRole::Customer, 404);

        $bookingCounts = $customer->bookings()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $totalSpent = Payment::query()
            ->whereHas('booking', fn ($q) => $q->where('user_id', $customer->id))
            ->where('status', PaymentStatus::Paid)
            ->sum('amount');

        return view('admin.customers.show', [
            'customer' => $customer,
            'totalBookings' => (int) $bookingCounts->sum(),
            'completedBookings' => (int) ($bookingCounts[BookingStatus::Completed->value] ?? 0),
            'cancelledBookings' => (int) ($bookingCounts[BookingStatus::Cancelled->value] ?? 0),
            'totalSpent' => (float) $totalSpent,
            'recentBookings' => $customer->bookings()->with('court')->latest('booking_date')->limit(10)->get(),
        ]);
    }

    public function toggleActive(User $customer): RedirectResponse
    {
        abort_unless($customer->role === UserRole::Customer, 404);

        $customer->update(['is_active' => ! $customer->is_active]);

        return back()->with('status', $customer->is_active ? 'Customer account enabled.' : 'Customer account disabled.');
    }

    /**
     * Promotes an existing customer account to staff, in place - rather
     * than requiring a brand-new account, which the unique email
     * constraint on `users` would reject if this person already has a
     * customer account (e.g. from testing a booking with their own
     * email before being hired). Their booking history stays attached
     * to the same account; the password is left untouched since only
     * they know it - the admin needs to set a new one via Staff > Edit
     * right after this.
     */
    public function convertToStaff(User $customer): RedirectResponse
    {
        abort_unless($customer->role === UserRole::Customer, 404);

        $customer->update(['role' => UserRole::Staff, 'is_active' => true]);

        return redirect()->route('admin.staff.edit', $customer)
            ->with('status', "{$customer->name} is now a staff account. Set a new password for them below.");
    }
}
