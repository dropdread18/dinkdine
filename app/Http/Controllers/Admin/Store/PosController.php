<?php

namespace App\Http\Controllers\Admin\Store;

use App\Enums\StoreSalePaymentMethod;
use App\Exceptions\StoreSaleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleCheckoutRequest;
use App\Models\Product;
use App\Models\StoreSale;
use App\Services\StoreSaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::query()->where('is_active', true)->with('category');

        if ($q = $request->query('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%");
            });
        }

        return view('admin.store.pos.index', [
            'products' => $query->orderBy('name')->get(),
            'q' => $q ?? '',
        ]);
    }

    /**
     * Shows the picked products back (whatever quantities were entered on
     * the product grid) plus the payment form - same shape as
     * WalkInBookingController::review(): one screen to select everything,
     * one review/payment screen, no multi-step "add to cart, keep
     * shopping" round trips (Store Phase 4 spec calls for speed, not a
     * full shopping-cart UI).
     */
    public function review(Request $request): View|RedirectResponse
    {
        try {
            $items = $this->decodeItems($request);
        } catch (StoreSaleException $e) {
            return redirect()->route('admin.store.pos.index')->withErrors(['cart' => $e->getMessage()]);
        }

        $subtotal = collect($items)->sum(fn (array $item) => $item['product']->selling_price * $item['quantity']);

        return view('admin.store.pos.review', [
            'items' => $items,
            'subtotal' => $subtotal,
            'quantities' => $request->query('quantities', []),
            'paymentMethods' => StoreSalePaymentMethod::cases(),
        ]);
    }

    public function checkout(StoreSaleCheckoutRequest $request, StoreSaleService $sales): RedirectResponse
    {
        try {
            $items = $this->decodeItems($request);

            $sale = $sales->checkout(
                cashier: $request->user(),
                items: collect($items)->map(fn (array $item) => [
                    'product_id' => $item['product']->id,
                    'quantity' => $item['quantity'],
                ])->all(),
                paymentMethod: StoreSalePaymentMethod::from($request->validated('payment_method')),
                amountPaid: (float) $request->validated('amount_paid'),
                discount: (float) ($request->validated('discount') ?? 0),
            );
        } catch (StoreSaleException $e) {
            return back()->withErrors(['cart' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.store.pos.receipt', $sale)->with('status', "Sale {$sale->sale_number} completed.");
    }

    public function receipt(StoreSale $sale): View
    {
        return view('admin.store.pos.receipt', [
            'sale' => $sale->load(['items', 'user']),
        ]);
    }

    /**
     * `quantities` arrives as a plain {product_id: quantity} map - every
     * row on the product grid submits its own input under its own id
     * (whether zero or not), so the product id doubles as the array key
     * with no need for a JSON-per-line encoding the way Walk-in's slots[]
     * needed (a slot has no natural unique key ahead of render time; a
     * product id already is one).
     *
     * @return array<int, array{product: Product, quantity: int}>
     *
     * @throws StoreSaleException
     */
    private function decodeItems(Request $request): array
    {
        $quantities = collect($request->input('quantities', []))
            ->map(fn ($qty) => (int) $qty)
            ->filter(fn (int $qty) => $qty > 0);

        if ($quantities->isEmpty()) {
            throw new StoreSaleException('Select at least one product.');
        }

        $products = Product::query()->where('is_active', true)->whereIn('id', $quantities->keys())->get()->keyBy('id');

        return $quantities->map(function (int $quantity, $productId) use ($products) {
            $product = $products->get((int) $productId);

            if (! $product) {
                throw new StoreSaleException('One of the selected products is no longer available. Please select your items again.');
            }

            return ['product' => $product, 'quantity' => $quantity];
        })->values()->all();
    }
}
