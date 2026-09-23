<?php

namespace App\Http\Controllers\Admin\Store;

use App\Enums\InventoryMovementType;
use App\Exceptions\InventoryAdjustmentException;
use App\Http\Controllers\Controller;
use App\Http\Requests\InventoryAdjustmentRequest;
use App\Http\Requests\StockInRequest;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::query()->where('is_active', true)->with('category');

        if ($q = $request->query('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    // Exact, not LIKE - a scanned barcode is a full code,
                    // and a partial match could surface the wrong product.
                    ->orWhere('barcode', $q);
            });
        }

        // "Needs attention" narrows to Low Stock + Out of Stock - the
        // quick view for "what do I need to reorder", not every active
        // product (Store Phase 3 spec: Low Stock/Out of Stock).
        $lowOnly = $request->boolean('low_stock');
        if ($lowOnly) {
            $query->whereColumn('stock_quantity', '<=', 'minimum_stock');
        }

        return view('admin.store.inventory.index', [
            'products' => $query->orderBy('name')->paginate(20)->withQueryString(),
            'q' => $q ?? '',
            'lowOnly' => $lowOnly,
        ]);
    }

    public function stockInForm(Product $product): View
    {
        return view('admin.store.inventory.stock-in', ['product' => $product]);
    }

    public function stockIn(StockInRequest $request, Product $product, InventoryService $inventory): RedirectResponse
    {
        $inventory->recordMovement(
            $product,
            InventoryMovementType::StockIn,
            (int) $request->validated('quantity'),
            $request->user(),
            $request->validated('reason'),
        );

        return redirect()->route('admin.store.inventory.index')->with('status', "Stock added for \"{$product->name}\".");
    }

    public function adjustForm(Product $product): View
    {
        return view('admin.store.inventory.adjust', [
            'product' => $product,
            'types' => InventoryMovementType::adjustmentTypes(),
        ]);
    }

    public function adjust(InventoryAdjustmentRequest $request, Product $product, InventoryService $inventory): RedirectResponse
    {
        try {
            $inventory->recordMovement(
                $product,
                InventoryMovementType::from($request->validated('type')),
                (int) $request->validated('quantity_change'),
                $request->user(),
                $request->validated('reason'),
            );
        } catch (InventoryAdjustmentException $e) {
            return back()->withErrors(['quantity_change' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.store.inventory.index')->with('status', "Stock adjusted for \"{$product->name}\".");
    }

    public function history(Product $product): View
    {
        return view('admin.store.inventory.history', [
            'product' => $product,
            'movements' => $product->movements()->with('user')->latest()->paginate(20),
        ]);
    }
}
