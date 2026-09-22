<?php

namespace App\Http\Controllers\Admin\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductCategoryRequest;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.store.categories.index', [
            'categories' => ProductCategory::withCount('products')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.store.categories.create');
    }

    public function store(ProductCategoryRequest $request): RedirectResponse
    {
        ProductCategory::create($request->validated());

        return redirect()->route('admin.store.categories.index')->with('status', 'Category created.');
    }

    public function edit(ProductCategory $category): View
    {
        return view('admin.store.categories.edit', ['category' => $category]);
    }

    public function update(ProductCategoryRequest $request, ProductCategory $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('admin.store.categories.index')->with('status', 'Category updated.');
    }

    /**
     * Deactivate/reactivate only - a category is never hard-deleted (Store
     * Phase 2 spec), so it can never strand a product that still
     * references it.
     */
    public function toggleActive(ProductCategory $category): RedirectResponse
    {
        $category->update(['is_active' => ! $category->is_active]);

        return back()->with('status', $category->is_active ? 'Category activated.' : 'Category deactivated.');
    }
}
