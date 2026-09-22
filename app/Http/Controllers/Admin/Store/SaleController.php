<?php

namespace App\Http\Controllers\Admin\Store;

use App\Enums\StoreSalePaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\StoreSale;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $query = StoreSale::query()->with('user');

        if ($q = $request->query('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->where('sale_number', 'like', "%{$q}%")
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$q}%"));
            });
        }

        if ($paymentMethod = $request->query('payment_method')) {
            $query->where('payment_method', $paymentMethod);
        }

        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $sales = $query->withCount('items')->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('admin.store.sales.index', [
            'sales' => $sales,
            'paymentMethods' => StoreSalePaymentMethod::cases(),
            'q' => $q ?? '',
            'from' => $from ?? '',
            'to' => $to ?? '',
        ]);
    }

    /**
     * Doubles as the post-checkout receipt (PosController::checkout()
     * redirects straight here) and the Sale Details page a Sales History
     * row links to - one view, not two, since a completed sale's own
     * detail page and its receipt are the same content.
     *
     * Staff only reach this route for a sale they personally rang up
     * (the handoff spec's "Future Staff POS Permission" section keeps
     * Sales History browsing admin-only) - 404, not 403, so a cashier
     * can't tell someone else's sale even exists by guessing an id, same
     * pattern as a customer viewing another customer's booking.
     */
    public function show(Request $request, StoreSale $sale): View
    {
        abort_unless($request->user()->isAdmin() || $sale->user_id === $request->user()->id, 404);

        return view('admin.store.sales.show', [
            'sale' => $sale->load(['items', 'user']),
        ]);
    }
}
