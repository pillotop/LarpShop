<?php

namespace App\Queries\Reports;

use App\Data\Reports\ReportPeriod;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use Illuminate\Support\Facades\DB;

class FulfillmentReportQuery
{
    public function overview(ReportPeriod $period): array
    {
        // Match the vendor fulfillment workflow: current paid-order status controls eligibility.
        $paidLines = DB::table('order_details')
            ->join('orders', 'orders.order_id', '=', 'order_details.order_id')
            ->where('orders.status', OrderStatus::Paid->value)
            ->where('orders.created_at', '<=', $period->until);
        $cohort = (clone $paidLines)
            ->when($period->since, fn ($query, $since) => $query->where('orders.created_at', '>=', $since));
        $counts = (clone $cohort)->select('fulfillment_status')
            ->selectRaw('COUNT(*) as line_count, COALESCE(SUM(quantity), 0) as units')
            ->groupBy('fulfillment_status')->get()->keyBy('fulfillment_status');
        $statuses = [];
        foreach (FulfillmentStatus::values() as $status) {
            $statuses[$status] = [
                'lines' => (int) ($counts[$status]->line_count ?? 0),
                'units' => (int) ($counts[$status]->units ?? 0),
            ];
        }
        $unknown = $counts->except(FulfillmentStatus::values());
        if ($unknown->isNotEmpty()) {
            $statuses['other'] = ['lines' => (int) $unknown->sum('line_count'), 'units' => (int) $unknown->sum('units')];
        }

        $awaitingDispatch = (clone $paidLines)->whereIn('fulfillment_status', [
            FulfillmentStatus::Unfulfilled->value, FulfillmentStatus::Processing->value,
        ]);

        return [
            'orders' => (clone $cohort)->distinct()->count('orders.order_id'),
            'lines' => (int) $counts->sum('line_count'),
            'units' => (int) $counts->sum('units'),
            'statuses' => $statuses,
            'awaiting_dispatch' => (clone $awaitingDispatch)->count(),
            'oldest_lines' => $awaitingDispatch
                ->join('products', 'products.product_id', '=', 'order_details.product_id')
                ->join('vendors', 'vendors.vendor_id', '=', 'products.vendor_id')
                ->orderBy('orders.created_at')->orderBy('order_detail_id')->limit(5)
                ->get(['order_detail_id', 'orders.order_id', 'orders.created_at as ordered_at', 'products.name', 'vendors.store_name', 'quantity']),
        ];
    }
}
