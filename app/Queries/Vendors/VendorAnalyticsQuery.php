<?php

namespace App\Queries\Vendors;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Product;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VendorAnalyticsQuery
{
    /**
     * Allowlisted time ranges.
     */
    public const RANGES = [
        '7_days' => 'Last 7 days',
        '30_days' => 'Last 30 days',
        'this_month' => 'This month',
        'all' => 'All time',
    ];

    /**
     * Get analytics summary and metrics for a specific vendor store.
     *
     * @return array<string, mixed>
     */
    public function getOverview(Vendor $vendor, string $range = '30_days'): array
    {
        $since = $this->rangeStart($range);

        // Base line query for paid orders containing products of this vendor
        $lineBase = DB::table('order_details')
            ->join('orders', 'orders.order_id', '=', 'order_details.order_id')
            ->join('products', 'products.product_id', '=', 'order_details.product_id')
            ->where('products.vendor_id', $vendor->vendor_id)
            ->where('orders.status', OrderStatus::Paid->value)
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('payments')
                    ->whereColumn('payments.order_id', 'orders.order_id')
                    ->where('payments.status', PaymentStatus::Succeeded->value);
            })
            ->when($since, fn ($q) => $q->where('orders.created_at', '>=', $since));

        $revenueAggregates = (clone $lineBase)
            ->selectRaw('COALESCE(SUM(order_details.quantity * order_details.unit_price), 0) as gross_revenue')
            ->selectRaw('COALESCE(SUM(order_details.quantity), 0) as units_sold')
            ->selectRaw('COUNT(DISTINCT orders.order_id) as total_orders')
            ->first();

        $grossRevenue = (float) ($revenueAggregates->gross_revenue ?? 0);
        $unitsSold = (int) ($revenueAggregates->units_sold ?? 0);
        $totalOrders = (int) ($revenueAggregates->total_orders ?? 0);
        $avgOrderValue = $totalOrders > 0 ? $grossRevenue / $totalOrders : 0;

        // Inventory metrics
        $productQuery = Product::where('vendor_id', $vendor->vendor_id);
        $totalProducts = (clone $productQuery)->count();
        $outOfStockCount = (clone $productQuery)->where('stock', 0)->count();
        $lowStockCount = (clone $productQuery)->where('stock', '>', 0)->where('stock', '<=', 5)->count();
        $inStockCount = (clone $productQuery)->where('stock', '>', 5)->count();
        $approvedCount = (clone $productQuery)->where('status', Product::STATUS_APPROVED)->count();
        $pendingCount = (clone $productQuery)->where('status', Product::STATUS_PENDING)->count();

        // Top 5 selling products for this vendor
        $topProducts = (clone $lineBase)
            ->select(
                'products.product_id',
                'products.name',
                'products.stock',
                DB::raw('SUM(order_details.quantity) as units_sold'),
                DB::raw('SUM(order_details.quantity * order_details.unit_price) as revenue')
            )
            ->groupBy('products.product_id', 'products.name', 'products.stock')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        // Low stock products alert list (stock <= 5)
        $lowStockProducts = (clone $productQuery)
            ->where('stock', '<=', 5)
            ->orderBy('stock', 'asc')
            ->limit(5)
            ->get(['product_id', 'name', 'stock', 'price', 'status']);

        return [
            'metrics' => [
                'gross_revenue' => $grossRevenue,
                'units_sold' => $unitsSold,
                'total_orders' => $totalOrders,
                'average_order_value' => $avgOrderValue,
                'total_products' => $totalProducts,
                'out_of_stock_count' => $outOfStockCount,
                'low_stock_count' => $lowStockCount,
                'in_stock_count' => $inStockCount,
                'approved_count' => $approvedCount,
                'pending_count' => $pendingCount,
            ],
            'top_products' => $topProducts,
            'low_stock_products' => $lowStockProducts,
            'range' => $range,
            'ranges' => self::RANGES,
        ];
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
