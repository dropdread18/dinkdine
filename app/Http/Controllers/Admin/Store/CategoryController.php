<?php

namespace App\Http\Controllers\Admin\Store;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Phase 1 placeholder - no `product_categories` table yet. See Phase 2 in
 * the handoff spec.
 */
class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.store.categories.index');
    }
}
