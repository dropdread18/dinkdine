<?php

namespace App\Http\Controllers\Admin\Store;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Phase 1 placeholder - no `products` table yet. See Phase 2 in the
 * handoff spec.
 */
class ProductController extends Controller
{
    public function index(): View
    {
        return view('admin.store.products.index');
    }
}
