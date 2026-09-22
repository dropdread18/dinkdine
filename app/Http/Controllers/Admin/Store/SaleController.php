<?php

namespace App\Http\Controllers\Admin\Store;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Phase 1 placeholder - no `store_sales`/`store_sale_items` tables yet.
 * See Phase 5 in the handoff spec.
 */
class SaleController extends Controller
{
    public function index(): View
    {
        return view('admin.store.sales.index');
    }
}
