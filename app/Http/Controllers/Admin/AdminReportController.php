<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminReportController extends Controller
{
    /**
     * Allowlisted time-range presets (UC-45).
     */
    public const RANGES = [
        '7_days' => 'Last 7 days',
        '30_days' => 'Last 30 days',
        'this_month' => 'This month',
        'all' => 'All time',
    ];

    /**
     * UC-45: Display the platform overview analytics report.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'range' => ['nullable', 'string', 'in:'.implode(',', array_keys(self::RANGES))],
        ]);

        $range = $validated['range'] ?? '30_days';
        $since = $this->rangeStart($range);

        $orders = fn () => Order::query()
            ->when($since, fn ($q) => $q->where('orders.created_at', '>=', $since));

        $revenueOrders = $orders()->whereHas('payments', fn ($query) => $query->where('status', PaymentStatus::Succeeded->value));
        $grossRevenue = (float) (clone $revenueOrders)->sum('total_amount');
        $revenueOrderCount = (clone $revenueOrders)->count();
        $totalOrders = $orders()->count();

        $statusCounts = $orders()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $metrics = [
            'gross_revenue' => $grossRevenue,
            'total_orders' => $totalOrders,
            'average_order_value' => $revenueOrderCount > 0 ? $grossRevenue / $revenueOrderCount : 0,
            'paid_rate' => $totalOrders > 0 ? round((($statusCounts[OrderStatus::Paid->value] ?? 0) / $totalOrders) * 100, 1) : 0,
            'problem_rate' => $totalOrders > 0
                ? round(((($statusCounts[OrderStatus::Expired->value] ?? 0) + ($statusCounts[OrderStatus::Cancelled->value] ?? 0)) / $totalOrders) * 100, 1)
                : 0,
            'active_vendors' => Vendor::whereIn('status', ['active', 'approved'])->count(),
            'approved_products' => Product::approved()->count(),
        ];

        $statusDistribution = [];
        foreach (OrderStatus::values() as $status) {
            $count = (int) ($statusCounts[$status] ?? 0);
            $statusDistribution[$status] = [
                'count' => $count,
                'percent' => $totalOrders > 0 ? round(($count / $totalOrders) * 100, 1) : 0,
            ];
        }

        $lineBase = fn () => DB::table('order_details')
            ->join('orders', 'orders.order_id', '=', 'order_details.order_id')
            ->join('products', 'products.product_id', '=', 'order_details.product_id')
            ->whereExists(function ($query) {
                $query->selectRaw('1')->from('payments')
                    ->whereColumn('payments.order_id', 'orders.order_id')
                    ->where('payments.status', PaymentStatus::Succeeded->value);
            })
            ->when($since, fn ($q) => $q->where('orders.created_at', '>=', $since));

        $topProducts = $lineBase()
            ->join('vendors', 'vendors.vendor_id', '=', 'products.vendor_id')
            ->select(
                'products.product_id',
                'products.name',
                'vendors.store_name',
                DB::raw('SUM(order_details.quantity) as units_sold'),
                DB::raw('SUM(order_details.quantity * order_details.unit_price) as revenue')
            )
            ->groupBy('products.product_id', 'products.name', 'vendors.store_name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        $topVendors = $lineBase()
            ->join('vendors', 'vendors.vendor_id', '=', 'products.vendor_id')
            ->select(
                'vendors.vendor_id',
                'vendors.store_name',
                DB::raw('SUM(order_details.quantity) as units_sold'),
                DB::raw('SUM(order_details.quantity * order_details.unit_price) as revenue')
            )
            ->groupBy('vendors.vendor_id', 'vendors.store_name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        $categoryDistribution = Category::query()
            ->withCount(['products' => fn ($q) => $q->where('status', Product::STATUS_APPROVED)])
            ->orderByDesc('products_count')
            ->orderBy('name')
            ->limit(10)
            ->get();

        $ranges = self::RANGES;

        return view('admin.reports.index', compact(
            'range',
            'ranges',
            'metrics',
            'statusDistribution',
            'topProducts',
            'topVendors',
            'categoryDistribution'
        ));
    }

    private function rangeStart(string $range): ?Carbon
    {
        return match ($range) {
            '7_days' => now()->subDays(7),
            '30_days' => now()->subDays(30),
            'this_month' => now()->startOfMonth(),
            default => null,
        };
    }
}
