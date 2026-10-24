<?php

namespace App\Services\Orders;

use App\Models\Order;
use Illuminate\Support\Collection;

class OrderManagementService
{
    /**
     * UC-44: Update the order status (e.g. mark disputed, refunded, cancelled).
     */
    public function updateStatus(Order $order, string $status): Order
    {
        $order->status = $status;
        $order->save();

        return $order;
    }

    /**
     * UC-44: Reconcile order total against item detail sum and group items by vendor.
     *
     * @return array{computedTotal: float, totalMatches: bool, byVendor: Collection}
     */
    public function reconcile(Order $order): array
    {
        $computedTotal = (float) $order->details->sum(fn ($d) => $d->quantity * $d->unit_price);
        $totalMatches = abs((float) $order->total_amount - $computedTotal) < 0.005;
        $byVendor = $order->details->groupBy(fn ($d) => $d->product?->vendor_id);

        return [
            'computedTotal' => $computedTotal,
            'totalMatches' => $totalMatches,
            'byVendor' => $byVendor,
        ];
    }
}
