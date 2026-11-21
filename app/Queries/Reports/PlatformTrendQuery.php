<?php

namespace App\Queries\Reports;

use App\Data\Reports\ReportPeriod;
use App\Models\Order;
use Carbon\CarbonImmutable;

class PlatformTrendQuery
{
    public function __construct(private CompletedSaleQuery $sales) {}

    public function forPeriod(ReportPeriod $period, string $salesSource): array
    {
        $monthly = $period->since === null;
        $format = $monthly ? 'Y-m' : 'Y-m-d';
        $sales = $this->sales->forPeriod($period, $salesSource);
        $orders = Order::query()
            ->when($period->since, fn ($query, $since) => $query->where('created_at', '>=', $since))
            ->where('created_at', $period->endInclusive ? '<=' : '<', $period->until);

        // Only aggregate rows cross into PHP; the buckets between them are filled with zero.
        $saleBucket = $monthly ? "DATE_FORMAT(first_payment.completed_at, '%Y-%m')" : 'DATE(first_payment.completed_at)';
        $orderBucket = $monthly ? "DATE_FORMAT(created_at, '%Y-%m')" : 'DATE(created_at)';
        $saleRows = $sales->selectRaw("{$saleBucket} as bucket, SUM(orders.total_amount) as revenue, COUNT(*) as paid_orders")
            ->groupBy('bucket')->orderBy('bucket')->get()->keyBy('bucket');
        $orderRows = $orders->selectRaw("{$orderBucket} as bucket, COUNT(*) as created_orders")
            ->groupBy('bucket')->orderBy('bucket')->get()->keyBy('bucket');

        $firstBucket = $saleRows->keys()->merge($orderRows->keys())->sort()->first();
        $start = $period->since ?? ($firstBucket
            ? CarbonImmutable::parse($firstBucket.'-01', $period->until->timezone)
            : $period->until);
        $cursor = $monthly ? $start->startOfMonth() : $start->startOfDay();
        $points = [];
        while ($cursor->lessThanOrEqualTo($period->until)) {
            $bucket = $cursor->format($format);
            $points[] = [
                'date' => $bucket,
                'revenue' => (float) ($saleRows[$bucket]->revenue ?? 0),
                'paid_orders' => (int) ($saleRows[$bucket]->paid_orders ?? 0),
                'created_orders' => (int) ($orderRows[$bucket]->created_orders ?? 0),
            ];
            $cursor = $monthly ? $cursor->addMonth() : $cursor->addDay();
        }

        return ['granularity' => $monthly ? 'monthly' : 'daily', 'points' => $points];
    }
}
