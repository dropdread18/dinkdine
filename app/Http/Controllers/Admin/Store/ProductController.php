<?php

namespace App\Http\Controllers\Admin\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::query()->with('category');

        if ($q = $request->query('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%");
            });
        }

        if ($categoryId = $request->query('category_id')) {
            $query->where('category_id', $categoryId);
        }

        // Any explicit choice ("active" or "inactive") narrows the list;
        // no choice at all (empty string, the "Any Status" option)
        // defaults to active-only - a deactivated product shouldn't
        // clutter the catalog staff see day to day, same reasoning as
        // Expired holds on the Bookings list, but it must stay one filter
        // choice away, not deleted or hidden without a way back.
        $status = $request->query('status', 'active');
        if ($status !== '') {
            $query->where('is_active', $status === 'active');
        }

        $products = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.store.products.index', [
            'products' => $products,
            'categories' => ProductCategory::orderBy('name')->get(),
            'q' => $q ?? '',
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('admin.store.products.create', [
            'categories' => ProductCategory::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        Product::create($request->validated());

        return redirect()->route('admin.store.products.index')->with('status', 'Product created.');
    }

    public function edit(Product $product): View
    {
        return view('admin.store.products.edit', [
            'product' => $product,
            // The product's own category might itself be deactivated -
            // still needs to appear in the dropdown (as the selected
            // value) so saving the form doesn't silently reassign it.
            'categories' => ProductCategory::where('is_active', true)
                ->orWhere('id', $product->category_id)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()->route('admin.store.products.index')->with('status', 'Product updated.');
    }

    /**
     * Deactivate/reactivate only - never a hard delete (Store Phase 2
     * spec). A product used in a completed sale must keep resolving on
     * that historical receipt; deactivating just removes it from the
     * active POS catalog.
     */
    public function toggleActive(Product $product): RedirectResponse
    {
        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('status', $product->is_active ? 'Product activated.' : 'Product deactivated.');
    }
}
