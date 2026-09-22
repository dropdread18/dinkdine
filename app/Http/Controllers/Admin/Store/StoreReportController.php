<?php

namespace App\Http\Controllers\Admin\Store;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Phase 1 placeholder. Named StoreReportController (not ReportController)
 * to avoid colliding with the existing court-booking
 * App\Http\Controllers\Admin\ReportController when both are imported in
 * routes/web.php. See Phase 6 in the handoff spec.
 */
class StoreReportController extends Controller
{
    public function index(): View
    {
        return view('admin.store.reports.index');
    }
}
