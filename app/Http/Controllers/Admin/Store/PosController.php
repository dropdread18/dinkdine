<?php

namespace App\Http\Controllers\Admin\Store;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Phase 1 placeholder - no cart/checkout logic yet. See Phase 4 in the
 * handoff spec (product search, cart, payment, receipt).
 */
class PosController extends Controller
{
    public function index(): View
    {
        return view('admin.store.pos.index');
    }
}
