<?php

namespace App\Queries\Reports;

use App\Data\Reports\ReportPeriod;
use App\Models\Product;
use App\Queries\Products\ProductCatalogQuery;

class InventoryReportQuery
{
    public const LOW_STOCK_THRESHOLD = 5;

    public function __construct(private ProductCatalogQuery $catalog) {}

    public function overview(ReportPeriod $period): array
    {
        $products = $this->catalog->constrainCollectionVisibility(Product::query())
            ->where('products.created_at', '<=', $period->until);
        $totals = (clone $products)
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(stock), 0) as available_units')
            ->selectRaw('COALESCE(SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END), 0) as out_of_stock')
            ->selectRaw('COALESCE(SUM(CASE WHEN stock BETWEEN 1 AND ? THEN 1 ELSE 0 END), 0) as low_stock', [self::LOW_STOCK_THRESHOLD])
            ->first();

        return [
            'threshold' => self::LOW_STOCK_THRESHOLD,
            'total_products' => (int) $totals->total,
            'available_units' => (int) $totals->available_units,
            'bands' => [
                'out_of_stock' => (int) $totals->out_of_stock,
                'low_stock' => (int) $totals->low_stock,
                'healthy_stock' => (int) $totals->total - (int) $totals->out_of_stock - (int) $totals->low_stock,
            ],
            'pending_products' => Product::query()->where('status', Product::STATUS_PENDING)
                ->where('created_at', '<=', $period->until)->count(),
            'attention_products' => (clone $products)
                ->where('stock', '<=', self::LOW_STOCK_THRESHOLD)
                ->with('vendor:vendor_id,store_name')
                ->orderBy('stock')->orderBy('product_id')->limit(5)
                ->get(['product_id', 'vendor_id', 'name', 'slug', 'stock']),
        ];
    }
}
