<?php

namespace App\Http\Controllers\Admin\Store;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Phase 1 placeholder - no `inventory_movements` table yet. See Phase 3
 * in the handoff spec.
 */
class InventoryController extends Controller
{
    public function index(): View
    {
        return view('admin.store.inventory.index');
    }
}
