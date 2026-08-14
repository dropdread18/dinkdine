<?php

use App\Http\Controllers\Admin\CourtController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BookingController;
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

// Dashboard content is still a placeholder — real modules (Requirements.md
// modules 12-13) will fill these in. The route, middleware, and layout are real.
Route::middleware(['auth', 'role:admin'])->get('/admin/dashboard', function () {
    return view('dashboard.admin');
})->name('admin.dashboard');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('courts', CourtController::class)->except(['show']);
});

Route::middleware(['auth', 'role:admin,staff'])->get('/staff/dashboard', function () {
    return view('dashboard.staff');
})->name('staff.dashboard');

Route::middleware(['auth', 'role:customer'])->group(function () {
    Route::get('/book', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/book/{court}', [BookingController::class, 'create'])->name('bookings.create');
    Route::post('/book/{court}', [BookingController::class, 'store'])->name('bookings.store');
    Route::get('/my-bookings', [BookingController::class, 'mine'])->name('bookings.mine');
});

Route::middleware('auth')->get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
