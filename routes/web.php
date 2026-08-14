<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Named stub so the `auth` middleware has somewhere to redirect guests.
// Replaced by the real login form/flow in the Authentication module.
Route::get('/login', function () {
    return 'Login placeholder — Authentication module not built yet';
})->name('login');

// Placeholder routes to verify role-based access control.
// Replaced by the real Admin Dashboard / Staff views (Requirements.md modules 12-13).
Route::middleware(['auth', 'role:admin'])->get('/admin/dashboard', function () {
    return 'Admin dashboard placeholder';
})->name('admin.dashboard');

Route::middleware(['auth', 'role:admin,staff'])->get('/staff/dashboard', function () {
    return 'Staff dashboard placeholder';
})->name('staff.dashboard');
