<?php

namespace App\Http\Controllers\Admin\Store;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Phase 1 (Store foundation): landing page for the Store module. Not the
 * same "store" as StoreMenuController (Henri's private hardcoded price
 * sheet for the physical counter) - this is the new POS/inventory system
 * that will eventually replace that hardcoded reference with real
 * Products/Categories/Inventory records. Only route access is admin-gated
 * here (role:admin, see routes/web.php); no owner-only restriction, unlike
 * StoreMenuController - see PaymentController::DELETE_ALLOWED_EMAIL for
 * this app's existing owner-only pattern if a Store operation ever needs
 * that same restriction.
 */
class StoreDashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.store.dashboard');
    }
}
