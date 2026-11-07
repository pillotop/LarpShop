<?php

namespace App\Services\Vendors;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VendorOrderFulfillmentService
{
    /**
     * UC-35: Update fulfillment and shipping status for either a single vendor-owned
     * order line or all of the vendor's order lines in a paid order.
     *
     * @return Collection<int, OrderDetail>
     */
    public function updateFulfillment(
        Vendor $vendor,
        Order $order,
        FulfillmentStatus $target,
        ?int $orderDetailId = null,
    ): Collection {
        $vendorId = (int) $vendor->vendor_id;

        return DB::transaction(function () use ($vendorId, $order, $target, $orderDetailId) {
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $hasVendorItems = $lockedOrder->details()
                ->whereHas('product', fn ($q) => $q->where('vendor_id', $vendorId))
                ->exists();

            if (! $hasVendorItems) {
                abort(403, __('management.messages.order_access'));
            }

            if ($orderDetailId !== null) {
                $targetDetail = OrderDetail::query()
                    ->with('product')
                    ->where('order_id', $lockedOrder->order_id)
                    ->whereKey($orderDetailId)
                    ->lockForUpdate()
                    ->first();

                if (! $targetDetail) {
                    abort(404, __('management.messages.order_item_missing'));
                }

                if ((int) $targetDetail->product?->vendor_id !== $vendorId) {
                    abort(403, __('management.messages.fulfillment_access'));
                }

                $details = new Collection([$targetDetail]);
            } else {
                $details = OrderDetail::query()
                    ->where('order_id', $lockedOrder->order_id)
                    ->whereHas('product', fn ($q) => $q->where('vendor_id', $vendorId))
                    ->orderBy('order_detail_id')
                    ->lockForUpdate()
                    ->get();
            }

            if ($lockedOrder->status !== OrderStatus::Paid) {
                throw ValidationException::withMessages([
                    'fulfillment_status' => __('management.messages.paid_only'),
                ]);
            }

            $now = now();

            foreach ($details as $detail) {
                $current = $detail->fulfillment_status instanceof FulfillmentStatus
                    ? $detail->fulfillment_status
                    : FulfillmentStatus::from((string) $detail->fulfillment_status);

                if (! $current->canTransitionTo($target)) {
                    throw ValidationException::withMessages([
                        'fulfillment_status' => __('management.messages.fulfillment_transition', ['current' => $current->label(), 'target' => $target->label()]),
                    ]);
                }

                if ($current === $target) {
                    continue;
                }

                $detail->fulfillment_status = $target;

                if (in_array($target, [FulfillmentStatus::Shipped, FulfillmentStatus::Delivered], true) && $detail->shipped_at === null) {
                    $detail->shipped_at = $now;
                }

                if ($target === FulfillmentStatus::Delivered && $detail->delivered_at === null) {
                    $detail->delivered_at = $now;
                }

                $detail->save();
            }

            return $details;
        }, attempts: 3);
    }
}
