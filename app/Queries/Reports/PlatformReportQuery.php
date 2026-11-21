<?php

namespace App\Queries\Reports;

use App\Data\Reports\ReportPeriod;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlatformReportQuery
{
    public const SALES_SOURCES = ['all', 'provider', 'simulated'];

    public function __construct(
        private CompletedSaleQuery $sales,
        private PlatformTrendQuery $trends,
        private PaymentReportQuery $payments,
        private InventoryReportQuery $inventory,
        private FulfillmentReportQuery $fulfillment,
        private VendorApplicationReportQuery $applications,
        private ReviewReportQuery $reviews,
    ) {}

    public function overview(ReportPeriod $period, string $salesSource): array
    {
        $statusCounts = Order::query()
            ->when($period->since, fn ($query, $since) => $query->where('created_at', '>=', $since))
            ->where('created_at', '<=', $period->until)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalOrders = (int) $statusCounts->sum();
        $sales = $this->sales->forPeriod($period, $salesSource);
        $salesMetrics = $this->sales->summary($period, $salesSource);

        $statusDistribution = [];
        foreach (OrderStatus::values() as $status) {
            $count = (int) ($statusCounts[$status] ?? 0);
            $statusDistribution[$status] = [
                'count' => $count,
                'percent' => $totalOrders > 0 ? round($count / $totalOrders * 100, 1) : 0,
            ];
        }

        return [
            'metrics' => [
                ...$salesMetrics,
                'total_orders' => $totalOrders,
                'paid_rate' => $statusDistribution[OrderStatus::Paid->value]['percent'],
                'problem_rate' => $totalOrders > 0
                    ? round((($statusCounts[OrderStatus::Cancelled->value] ?? 0) + ($statusCounts[OrderStatus::Expired->value] ?? 0)) / $totalOrders * 100, 1)
                    : 0,
                'active_vendors' => Vendor::active()->count(),
                'approved_products' => Product::approved()->count(),
            ],
            'comparison' => $this->comparison($period, $salesSource, [...$salesMetrics, 'total_orders' => $totalOrders]),
            'trends' => $this->trends->forPeriod($period, $salesSource),
            'paymentReport' => $this->payments->overview($period),
            'inventoryReport' => $this->inventory->overview($period),
            'fulfillmentReport' => $this->fulfillment->overview($period),
            'applicationReport' => $this->applications->overview($period),
            'reviewReport' => $this->reviews->overview($period),
            'statusDistribution' => $statusDistribution,
            'topProducts' => $this->topProducts($sales),
            'topVendors' => $this->topVendors($sales),
            'categoryDistribution' => Category::query()
                ->withCount(['products' => fn ($query) => $query->where('status', Product::STATUS_APPROVED)])
                ->orderByDesc('products_count')
                ->orderBy('name')
                ->limit(10)
                ->get(),
            'undatedSuccesses' => Payment::query()
                ->where('status', PaymentStatus::Succeeded->value)
                ->whereNull('completed_at')
                ->where('created_at', '<=', $period->until)
                ->count(),
        ];
    }

    private function comparison(ReportPeriod $period, string $salesSource, array $current): ?array
    {
        $previousPeriod = $period->previous();
        if ($previousPeriod === null) {
            return null;
        }

        $previous = [
            ...$this->sales->summary($previousPeriod, $salesSource),
            'total_orders' => Order::query()
                ->where('created_at', '>=', $previousPeriod->since)
                ->where('created_at', '<', $previousPeriod->until)
                ->count(),
        ];
        $changes = [];
        foreach ($previous as $metric => $value) {
            $delta = $current[$metric] - $value;
            $changes[$metric] = [
                'previous' => $value,
                'delta' => $delta,
                'percent' => $value > 0 ? round($delta / $value * 100, 1) : ($delta == 0 ? 0.0 : null),
            ];
        }

        return ['period' => $previousPeriod, 'metrics' => $changes];
    }

    private function saleLines(EloquentBuilder $sales): Builder
    {
        return DB::table('order_details')
            ->joinSub((clone $sales)->select('orders.order_id'), 'sales', 'sales.order_id', '=', 'order_details.order_id')
            ->join('products', 'products.product_id', '=', 'order_details.product_id')
            ->join('vendors', 'vendors.vendor_id', '=', 'products.vendor_id');
    }

    private function topProducts(EloquentBuilder $sales): Collection
    {
        return $this->saleLines($sales)
            ->select('products.product_id', 'products.name', 'vendors.store_name')
            ->selectRaw('SUM(order_details.quantity) as units_sold, SUM(order_details.quantity * order_details.unit_price) as revenue')
            ->groupBy('products.product_id', 'products.name', 'vendors.store_name')
            ->orderByDesc('revenue')
            ->orderBy('products.product_id')
            ->limit(5)
            ->get();
    }

    private function topVendors(EloquentBuilder $sales): Collection
    {
        return $this->saleLines($sales)
            ->select('vendors.vendor_id', 'vendors.store_name')
            ->selectRaw('SUM(order_details.quantity) as units_sold, SUM(order_details.quantity * order_details.unit_price) as revenue')
            ->groupBy('vendors.vendor_id', 'vendors.store_name')
            ->orderByDesc('revenue')
            ->orderBy('vendors.vendor_id')
            ->limit(5)
            ->get();
    }
}
