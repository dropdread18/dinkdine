<?php

namespace App\Services;

use App\Enums\StoreSalePaymentMethod;
use App\Models\Product;
use App\Models\StoreSale;
use App\Models\StoreSaleItem;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Read-only aggregate queries over Store Sales - deliberately its own
 * service, not folded into the existing court-booking ReportingService,
 * per the handoff spec's "keep Store Sales and Court Booking Payments
 * independent" note (a unified reporting layer is explicitly left for
 * later). Same whereDate() (not whereBetween()) pattern as
 * ReportingService uses for date-range filtering - see that class's
 * docblock for why whereBetween() silently drops rows on SQLite.
 */
class StoreReportingService
{
    /**
     * @return array{total: float, count: int}
     */
    public function dailySales(CarbonInterface $start, CarbonInterface $end): array
    {
        $query = $this->inRange(StoreSale::query(), $start, $end);

        return [
            'total' => (float) $query->sum('total'),
            'count' => $query->count(),
        ];
    }

    /**
     * One row per calendar day in the range, zero-filled - grouped in PHP
     * rather than a DATE()/strftime() grouped query, same reasoning as
     * ReportController::last7DaysRevenue().
     *
     * @return Collection<int, array{date: string, label: string, total: float}>
     */
    public function salesByDate(CarbonInterface $start, CarbonInterface $end): Collection
    {
        $byDate = $this->inRange(StoreSale::query(), $start, $end)
            ->get(['total', 'created_at'])
            ->groupBy(fn (StoreSale $sale) => $sale->created_at->toDateString())
            ->map(fn (Collection $sales) => (float) $sales->sum('total'));

        return collect(CarbonPeriod::create($start, $end))->map(fn ($date) => [
            'date' => $date->toDateString(),
            'label' => $date->format('M j'),
            'total' => $byDate->get($date->toDateString(), 0.0),
        ]);
    }

    /**
     * @return Collection<int, array{method: StoreSalePaymentMethod, total: float, count: int}>
     */
    public function paymentBreakdown(CarbonInterface $start, CarbonInterface $end): Collection
    {
        $sums = $this->inRange(StoreSale::query(), $start, $end)
            ->selectRaw('payment_method, sum(total) as total, count(*) as aggregate')
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method');

        return collect(StoreSalePaymentMethod::cases())->map(fn (StoreSalePaymentMethod $method) => [
            'method' => $method,
            'total' => (float) ($sums[$method->value]->total ?? 0),
            'count' => (int) ($sums[$method->value]->aggregate ?? 0),
        ]);
    }

    /**
     * Grouped by the item's own snapshotted product_name, not product_id -
     * consistent with the rest of Store Sales treating that snapshot as
     * the historical truth (Store Phase 4 spec §16). If a product was
     * ever renamed, its sales from before and after the rename
     * legitimately read as separate rows here, the same way two old
     * receipts would each show the name that was true when they were
     * made.
     *
     * @return Collection<int, object{product_name: string, quantity_sold: int, revenue: float}>
     */
    public function productSales(CarbonInterface $start, CarbonInterface $end): Collection
    {
        return StoreSaleItem::query()
            ->whereHas('sale', fn ($query) => $this->inRange($query, $start, $end))
            ->selectRaw('product_name, sum(quantity) as quantity_sold, sum(subtotal) as revenue')
            ->groupBy('product_name')
            ->orderByDesc('revenue')
            ->get();
    }

    /**
     * A live snapshot, not date-ranged - "how is stock looking right now",
     * same Low Stock/Out of Stock rules as the Inventory list (Store
     * Phase 3 spec).
     *
     * @return array{total: int, ok: int, low: int, out: int}
     */
    public function inventoryStatus(): array
    {
        $products = Product::where('is_active', true)->get(['stock_quantity', 'minimum_stock']);

        $out = $products->filter(fn (Product $p) => $p->isOutOfStock())->count();
        $low = $products->filter(fn (Product $p) => $p->isLowStock())->count();

        return [
            'total' => $products->count(),
            'out' => $out,
            'low' => $low,
            'ok' => $products->count() - $out - $low,
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<StoreSale>  $query
     * @return \Illuminate\Database\Eloquent\Builder<StoreSale>
     */
    private function inRange($query, CarbonInterface $start, CarbonInterface $end)
    {
        return $query
            ->whereDate('created_at', '>=', $start->toDateString())
            ->whereDate('created_at', '<=', $end->toDateString());
    }
}
