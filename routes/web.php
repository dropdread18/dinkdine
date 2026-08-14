<?php

use App\Http\Controllers\Admin\CourtController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Staff\BookingController as StaffBookingController;
use App\Http\Controllers\Staff\WalkInBookingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

// Admin dashboard content is still a placeholder — real modules
// (Requirements.md module 13) will fill this in.
Route::middleware(['auth', 'role:admin'])->get('/admin/dashboard', function () {
    return view('dashboard.admin');
})->name('admin.dashboard');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('courts', CourtController::class)->except(['show']);
});

// Payments is an admin-only capability (Requirements.md §51 lists it under
// Admin Navigation only - Staff Navigation §52 does not include it).
Route::middleware(['auth', 'role:admin'])->prefix('manage')->name('manage.')->group(function () {
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::patch('payments/{payment}/mark-paid', [PaymentController::class, 'markPaid'])->name('payments.mark-paid');
    Route::patch('payments/{payment}/mark-failed', [PaymentController::class, 'markFailed'])->name('payments.mark-failed');
    Route::patch('payments/{payment}/refund', [PaymentController::class, 'refund'])->name('payments.refund');
});

Route::middleware(['auth', 'role:admin,staff'])->get('/staff/dashboard', function () {
    return view('dashboard.staff', [
        'todaysBookings' => \App\Models\Booking::query()
            ->with(['court', 'user'])
            ->whereDate('booking_date', now())
            ->orderBy('start_time')
            ->get(),
    ]);
})->name('staff.dashboard');

Route::middleware(['auth', 'role:admin,staff'])->prefix('manage')->name('manage.')->group(function () {
    Route::get('bookings', [StaffBookingController::class, 'index'])->name('bookings.index');
    Route::patch('bookings/{booking}/cancel', [StaffBookingController::class, 'cancel'])->name('bookings.cancel');
    Route::get('bookings/{booking}/reschedule', [StaffBookingController::class, 'reschedule'])->name('bookings.reschedule');
    Route::get('bookings/{booking}/reschedule/{court}', [StaffBookingController::class, 'rescheduleForm'])->name('bookings.reschedule-form');
    Route::put('bookings/{booking}/reschedule/{court}', [StaffBookingController::class, 'rescheduleUpdate'])->name('bookings.reschedule-update');

    Route::get('walk-in', [WalkInBookingController::class, 'index'])->name('walkin.index');
    Route::get('walk-in/{court}', [WalkInBookingController::class, 'create'])->name('walkin.create');
    Route::post('walk-in/{court}', [WalkInBookingController::class, 'store'])->name('walkin.store');
});

// Open to guests too (DEC-003) - the BookingGrid Livewire component
// handles account lookup/creation for anyone not already logged in.
// Still blocked for staff/admin (they have their own walk-in flow).
Route::middleware('customer_or_guest')->get('/book', [BookingController::class, 'index'])->name('bookings.index');

Route::middleware(['auth', 'role:customer'])->group(function () {
    Route::get('/book/{court}', [BookingController::class, 'create'])->name('bookings.create');
    Route::post('/book/{court}', [BookingController::class, 'store'])->name('bookings.store');
    Route::get('/my-bookings', [BookingController::class, 'mine'])->name('bookings.mine');

    Route::patch('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::get('/bookings/{booking}/reschedule', [BookingController::class, 'reschedule'])->name('bookings.reschedule');
    Route::get('/bookings/{booking}/reschedule/{court}', [BookingController::class, 'rescheduleForm'])->name('bookings.reschedule-form');
    Route::put('/bookings/{booking}/reschedule/{court}', [BookingController::class, 'rescheduleUpdate'])->name('bookings.reschedule-update');
});

Route::middleware('auth')->get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
