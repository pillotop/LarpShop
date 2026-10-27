<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class OrderManagementService
{
    public function __construct(private OrderLifecycleService $lifecycle) {}

    /**
     * Administration uses the same transitions and inventory rules as checkout.
     */
    public function updateStatus(Order $order, OrderStatus $status): Order
    {
        if ($status !== OrderStatus::Cancelled) {
            throw ValidationException::withMessages([
                'status' => 'Only eligible order cancellation is available here. Payment status is managed by the payment flow.',
            ]);
        }

        return $this->lifecycle->cancel($order);
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
