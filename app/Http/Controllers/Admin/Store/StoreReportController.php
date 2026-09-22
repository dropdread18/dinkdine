<?php

namespace App\Http\Controllers\Admin\Store;

use App\Http\Controllers\Controller;
use App\Services\StoreReportingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Named StoreReportController (not ReportController) to avoid colliding
 * with the existing court-booking App\Http\Controllers\Admin\
 * ReportController when both are imported in routes/web.php.
 */
class StoreReportController extends Controller
{
    public function index(Request $request, StoreReportingService $reports): View
    {
        [$start, $end, $range] = $this->resolveRange($request);

        return view('admin.store.reports.index', [
            'start' => $start,
            'end' => $end,
            'range' => $range,
            'dailySales' => $reports->dailySales($start, $end),
            'salesByDate' => $reports->salesByDate($start, $end),
            'paymentBreakdown' => $reports->paymentBreakdown($start, $end),
            'productSales' => $reports->productSales($start, $end),
            'inventoryStatus' => $reports->inventoryStatus(),
        ]);
    }

    /**
     * Same today/week/month/custom range switcher as the court-booking
     * Reports page (Admin\ReportController::resolveRange()) - kept as its
     * own copy rather than a shared helper, consistent with Store staying
     * independent of the booking domain throughout this app.
     *
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function resolveRange(Request $request): array
    {
        $range = $request->query('range', 'today');
        $today = Carbon::today();

        return match ($range) {
            'week' => [$today->copy()->startOfWeek(), $today->copy()->endOfWeek(), 'week'],
            'month' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth(), 'month'],
            'custom' => [
                Carbon::parse($request->query('start', $today->toDateString())),
                Carbon::parse($request->query('end', $today->toDateString())),
                'custom',
            ],
            default => [$today->copy(), $today->copy(), 'today'],
        };
    }
}
