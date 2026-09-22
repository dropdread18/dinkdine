<?php

use App\Http\Controllers\Admin\CourtController;
use App\Http\Controllers\Admin\CourtMaintenanceController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OpenPlaySessionController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\Store\CategoryController as StoreCategoryController;
use App\Http\Controllers\Admin\Store\InventoryController as StoreInventoryController;
use App\Http\Controllers\Admin\Store\PosController as StorePosController;
use App\Http\Controllers\Admin\Store\ProductController as StoreProductController;
use App\Http\Controllers\Admin\Store\SaleController as StoreSaleController;
use App\Http\Controllers\Admin\Store\StoreDashboardController;
use App\Http\Controllers\Admin\Store\StoreReportController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StoreMenuController;
use App\Http\Controllers\Staff\BookingController as StaffBookingController;
use App\Http\Controllers\Staff\CheckInController;
use App\Http\Controllers\Staff\ScheduleController;
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

Route::get('/contact', fn () => view('contact'))->name('contact');

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

// An Open Play organizer is not facility staff - they only need to see
// the read-only schedule and manage Open Play sessions, never
// Courts/Maintenance/Customers/Staff, so these are deliberately their
// own group rather than living inside the admin-only block below.
Route::middleware(['auth', 'role:admin,organizer'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('open-play', OpenPlaySessionController::class)->except(['show'])->parameters(['open-play' => 'session']);
});

Route::middleware(['auth', 'role:admin,organizer'])->prefix('manage')->name('manage.')->group(function () {
    Route::get('schedule', [ScheduleController::class, 'index'])->name('schedule.index');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('courts', CourtController::class)->except(['show']);
    Route::resource('maintenance', CourtMaintenanceController::class)->except(['show']);
    Route::resource('payment-methods', PaymentMethodController::class)->except(['show']);

    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::patch('customers/{customer}/toggle-active', [CustomerController::class, 'toggleActive'])->name('customers.toggle-active');
    Route::patch('customers/{customer}/convert-to-staff', [CustomerController::class, 'convertToStaff'])->name('customers.convert-to-staff');

    Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
    Route::get('staff/create', [StaffController::class, 'create'])->name('staff.create');
    Route::post('staff', [StaffController::class, 'store'])->name('staff.store');
    Route::get('staff/{staff}/edit', [StaffController::class, 'edit'])->name('staff.edit');
    Route::put('staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
    Route::patch('staff/{staff}/toggle-active', [StaffController::class, 'toggleActive'])->name('staff.toggle-active');
});

// Store (POS/products/inventory/sales) - per the Claude Code Handoff
// Specification. Staff/Organizer/Customer get a 403 here via
// EnsureUserHasRole on every route in the admin-only group below,
// including on a direct URL visit - there is no hidden-URL-only gating.
//
// Staff gets a narrow exception (spec section 35, "Future Staff POS
// Permission"): POS itself, and viewing the receipt of a sale they
// personally rang up (the last step of the POS flow - see
// StoreSaleController::show()'s ownership check). Everything else -
// Products, Categories, Inventory, the Sales History list, Reports,
// and the Store overview page - stays admin-only. A cashier can sell
// products but can't change price/cost, touch inventory, see profit,
// or browse other sales.
Route::middleware(['auth', 'role:admin,staff'])->prefix('admin/store')->name('admin.store.')->group(function () {
    // Phase 4: POS checkout writes through StoreSaleService, which is
    // the only place a sale is ever created - see that class.
    Route::get('pos', [StorePosController::class, 'index'])->name('pos.index');
    Route::get('pos/review', [StorePosController::class, 'review'])->name('pos.review');
    Route::post('pos/checkout', [StorePosController::class, 'checkout'])->name('pos.checkout');

    // Phase 5: doubles as the post-checkout receipt (PosController::
    // checkout() redirects straight here) and the Sale Details page a
    // Sales History row links to - staff can only reach it for their
    // own sale (404 otherwise, see the controller), admin can view any.
    Route::get('sales/{sale}', [StoreSaleController::class, 'show'])->name('sales.show');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin/store')->name('admin.store.')->group(function () {
    Route::get('/', [StoreDashboardController::class, 'index'])->name('index');

    // Phase 2: full CRUD (create/edit/deactivate) - see StoreProductController/
    // StoreCategoryController. Neither ever exposes a destroy() route: a
    // category or product is deactivated, never hard-deleted (Store Phase 2
    // spec - a product used in a completed sale must keep resolving on that
    // historical receipt).
    Route::get('products', [StoreProductController::class, 'index'])->name('products.index');
    Route::get('products/create', [StoreProductController::class, 'create'])->name('products.create');
    Route::post('products', [StoreProductController::class, 'store'])->name('products.store');
    Route::get('products/{product}/edit', [StoreProductController::class, 'edit'])->name('products.edit');
    Route::put('products/{product}', [StoreProductController::class, 'update'])->name('products.update');
    Route::patch('products/{product}/toggle-active', [StoreProductController::class, 'toggleActive'])->name('products.toggle-active');

    Route::get('categories', [StoreCategoryController::class, 'index'])->name('categories.index');
    Route::get('categories/create', [StoreCategoryController::class, 'create'])->name('categories.create');
    Route::post('categories', [StoreCategoryController::class, 'store'])->name('categories.store');
    Route::get('categories/{category}/edit', [StoreCategoryController::class, 'edit'])->name('categories.edit');
    Route::put('categories/{category}', [StoreCategoryController::class, 'update'])->name('categories.update');
    Route::patch('categories/{category}/toggle-active', [StoreCategoryController::class, 'toggleActive'])->name('categories.toggle-active');

    // Phase 3: Stock In / Adjust write through InventoryService, which is
    // the only place stock_quantity ever changes - see that class. No
    // destroy/edit on a movement itself, it's an append-only ledger.
    Route::get('inventory', [StoreInventoryController::class, 'index'])->name('inventory.index');
    Route::get('inventory/{product}/stock-in', [StoreInventoryController::class, 'stockInForm'])->name('inventory.stock-in-form');
    Route::post('inventory/{product}/stock-in', [StoreInventoryController::class, 'stockIn'])->name('inventory.stock-in');
    Route::get('inventory/{product}/adjust', [StoreInventoryController::class, 'adjustForm'])->name('inventory.adjust-form');
    Route::post('inventory/{product}/adjust', [StoreInventoryController::class, 'adjust'])->name('inventory.adjust');
    Route::get('inventory/{product}/history', [StoreInventoryController::class, 'history'])->name('inventory.history');

    // sales.show (the receipt/detail page) is registered above, in the
    // role:admin,staff group - not here.
    Route::get('sales', [StoreSaleController::class, 'index'])->name('sales.index');

    Route::get('reports', [StoreReportController::class, 'index'])->name('reports.index');
});

// Payments (viewing the list, marking paid, marking failed, refunding) is
// admin-only. Staff briefly had view + mark-paid access (they collect
// walk-in payment in person) but owner feedback reverted that - Payments
// is back to a single admin-only group.
Route::middleware(['auth', 'role:admin'])->prefix('manage')->name('manage.')->group(function () {
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::patch('payments/{payment}/mark-paid', [PaymentController::class, 'markPaid'])->name('payments.mark-paid');
    Route::patch('payments/bulk-mark-paid', [PaymentController::class, 'bulkMarkPaid'])->name('payments.bulk-mark-paid');
    Route::patch('payments/{payment}/mark-failed', [PaymentController::class, 'markFailed'])->name('payments.mark-failed');
    Route::patch('payments/{payment}/refund', [PaymentController::class, 'refund'])->name('payments.refund');
    // Permanently deletes bookings/payments, not just "mark failed" - kept
    // admin-gated at the route level, but PaymentController::bulkDelete()
    // further restricts it to Henri's own account specifically (see that
    // method) since another admin on this system shouldn't have it either.
    Route::delete('payments/bulk-delete', [PaymentController::class, 'bulkDelete'])->name('payments.bulk-delete');

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
    Route::get('bookings', [StaffBookingController::class, 'index'])->name('bookings.index');
    Route::patch('bookings/{booking}/cancel', [StaffBookingController::class, 'cancel'])->name('bookings.cancel');
    Route::get('bookings/{booking}/reschedule', [StaffBookingController::class, 'reschedule'])->name('bookings.reschedule');
    Route::get('bookings/{booking}/reschedule/{court}', [StaffBookingController::class, 'rescheduleForm'])->name('bookings.reschedule-form');
    Route::put('bookings/{booking}/reschedule/{court}', [StaffBookingController::class, 'rescheduleUpdate'])->name('bookings.reschedule-update');

    Route::get('walk-in', [WalkInBookingController::class, 'index'])->name('walkin.index');
    // No {court} route param any more - a walk-in submission can span
    // multiple courts and times in one go (see the checkbox grid on
    // walkin.index), so which court(s) are involved now travels in the
    // request body/query (a `slots[]` array) instead of the URL.
    Route::get('walk-in/review', [WalkInBookingController::class, 'review'])->name('walkin.review');
    Route::post('walk-in', [WalkInBookingController::class, 'store'])->name('walkin.store');

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

// Henri's own private, searchable store-menu reference - see
// StoreMenuController for why this isn't under role:admin or linked from
// any nav.
Route::middleware('auth')->get('/store-menu', [StoreMenuController::class, 'index'])->name('store-menu.index');

Route::middleware('auth')->get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
Route::middleware('auth')->get('/bookings/{booking}/receipt', [BookingController::class, 'receipt'])->name('bookings.receipt');
Route::middleware('auth')->get('/bookings/{booking}/payment-proof', [BookingController::class, 'paymentProof'])->name('bookings.payment-proof');
