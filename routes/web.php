<?php

use App\Http\Controllers\Admin\CourtController;
use App\Http\Controllers\Admin\CourtMaintenanceController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OpenPlaySessionController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Staff\BookingController as StaffBookingController;
use App\Http\Controllers\Staff\CheckInController;
use App\Http\Controllers\Staff\WalkInBookingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function (\App\Services\AvailabilityService $availability) {
    return view('home', [
        'courts' => \App\Models\Court::query()
            ->where('status', \App\Enums\CourtStatus::Active)
            ->orderBy('sort_order')->orderBy('court_number')
            ->get(),
        'businessHours' => \App\Models\BusinessHour::orderBy('day_of_week')->get(),
        'todayAvailability' => $availability->forDate(now()->toDateString()),
    ]);
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:5,1');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1');
});

// Deliberately NOT behind 'guest' middleware, unlike login/register above -
// a logged-in guest-checkout or walk-in account has an unknowable random
// password (see BookingGrid::resolveCustomer(), WalkInBookingController)
// and no way to learn it via "current password" (Profile's Change
// Password form requires it). This is their only way back in. Safe to
// allow while authenticated: Password::reset()/sendResetLink() are
// authorized by the emailed token + email match, not by auth state, and
// NewPasswordController::store() never touches the current session.
Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email')->middleware('throttle:3,1');

Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store')->middleware('throttle:5,1');

Route::middleware('auth')->post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

Route::middleware(['auth', 'role:admin'])->get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('courts', CourtController::class)->except(['show']);
    Route::resource('maintenance', CourtMaintenanceController::class)->except(['show']);
    Route::resource('open-play', OpenPlaySessionController::class)->except(['show'])->parameters(['open-play' => 'session']);

    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::patch('customers/{customer}/toggle-active', [CustomerController::class, 'toggleActive'])->name('customers.toggle-active');

    Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
    Route::get('staff/create', [StaffController::class, 'create'])->name('staff.create');
    Route::post('staff', [StaffController::class, 'store'])->name('staff.store');
    Route::get('staff/{staff}/edit', [StaffController::class, 'edit'])->name('staff.edit');
    Route::put('staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
    Route::patch('staff/{staff}/toggle-active', [StaffController::class, 'toggleActive'])->name('staff.toggle-active');
});

// Marking a payment paid/failed and refunding it used to be entirely
// admin-only (Requirements.md §51/§52). Owner feedback: staff collect
// walk-in payment in person and need to confirm it themselves, so viewing
// the list and marking paid are now role:admin,staff (grouped with the
// rest of staff's operational routes below). Failing/refunding a payment
// stays admin-only here - a materially more sensitive action than
// confirming a payment was received.
Route::middleware(['auth', 'role:admin'])->prefix('manage')->name('manage.')->group(function () {
    Route::patch('payments/{payment}/mark-failed', [PaymentController::class, 'markFailed'])->name('payments.mark-failed');
    Route::patch('payments/{payment}/refund', [PaymentController::class, 'refund'])->name('payments.refund');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export/bookings', [ReportController::class, 'exportBookings'])->name('reports.export-bookings');
    Route::get('reports/export/payments', [ReportController::class, 'exportPayments'])->name('reports.export-payments');

    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    Route::put('settings/business-hours', [SettingController::class, 'updateBusinessHours'])->name('settings.business-hours.update');
});

// Staff get the exact same operational dashboard as admin (same controller,
// same view) - both roles need to immediately see pending payments and
// today's bookings the moment they log in, not just admin.
Route::middleware(['auth', 'role:admin,staff'])->get('/staff/dashboard', [DashboardController::class, 'index'])->name('staff.dashboard');

Route::middleware(['auth', 'role:admin,staff'])->prefix('manage')->name('manage.')->group(function () {
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::patch('payments/{payment}/mark-paid', [PaymentController::class, 'markPaid'])->name('payments.mark-paid');

    Route::get('bookings', [StaffBookingController::class, 'index'])->name('bookings.index');
    Route::patch('bookings/{booking}/cancel', [StaffBookingController::class, 'cancel'])->name('bookings.cancel');
    Route::get('bookings/{booking}/reschedule', [StaffBookingController::class, 'reschedule'])->name('bookings.reschedule');
    Route::get('bookings/{booking}/reschedule/{court}', [StaffBookingController::class, 'rescheduleForm'])->name('bookings.reschedule-form');
    Route::put('bookings/{booking}/reschedule/{court}', [StaffBookingController::class, 'rescheduleUpdate'])->name('bookings.reschedule-update');

    Route::get('walk-in', [WalkInBookingController::class, 'index'])->name('walkin.index');
    Route::get('walk-in/{court}', [WalkInBookingController::class, 'create'])->name('walkin.create');
    Route::post('walk-in/{court}', [WalkInBookingController::class, 'store'])->name('walkin.store');

    Route::get('check-in', [CheckInController::class, 'index'])->name('checkin.index');
    Route::patch('check-in/bookings/{booking}/check-in', [CheckInController::class, 'checkIn'])->name('checkin.bookings.check-in');
    Route::patch('check-in/bookings/{booking}/complete', [CheckInController::class, 'markCompleted'])->name('checkin.bookings.complete');
    Route::patch('check-in/bookings/{booking}/no-show', [CheckInController::class, 'markNoShow'])->name('checkin.bookings.no-show');
    Route::patch('check-in/courts/{court}/status', [CheckInController::class, 'updateCourtStatus'])->name('checkin.courts.update-status');
});

// Open to guests too (DEC-003) - the BookingGrid Livewire component
// handles account lookup/creation for anyone not already logged in.
// Still blocked for staff/admin (they have their own walk-in flow).
Route::middleware('customer_or_guest')->get('/book', [BookingController::class, 'index'])->name('bookings.index');

Route::middleware(['auth', 'role:customer'])->group(function () {
    Route::get('/book/{court}', [BookingController::class, 'create'])->name('bookings.create');
    Route::post('/book/{court}', [BookingController::class, 'store'])->name('bookings.store');
    Route::get('/my-bookings', [BookingController::class, 'mine'])->name('bookings.mine');
    Route::get('/booking-confirmed', [BookingController::class, 'confirmation'])->name('bookings.confirmation');

    Route::patch('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::post('/bookings/{booking}/continue-payment', [BookingController::class, 'continuePayment'])->name('bookings.continue-payment');
    Route::get('/bookings/{booking}/reschedule', [BookingController::class, 'reschedule'])->name('bookings.reschedule');
    Route::get('/bookings/{booking}/reschedule/{court}', [BookingController::class, 'rescheduleForm'])->name('bookings.reschedule-form');
    Route::put('/bookings/{booking}/reschedule/{court}', [BookingController::class, 'rescheduleUpdate'])->name('bookings.reschedule-update');
});

// Any authenticated role - customer, staff, or admin all need to be able to
// change their own password (staff/admin previously had no route to this at
// all, since it lived in the customer-only group above by oversight).
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');
});

Route::middleware('auth')->get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
Route::middleware('auth')->get('/bookings/{booking}/receipt', [BookingController::class, 'receipt'])->name('bookings.receipt');
Route::middleware('auth')->get('/bookings/{booking}/payment-proof', [BookingController::class, 'paymentProof'])->name('bookings.payment-proof');
