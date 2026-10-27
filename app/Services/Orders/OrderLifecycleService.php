<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Inventory\InventoryReservationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderLifecycleService
{
    public function __construct(
        private InventoryReservationService $inventory,
    ) {}

    public function cancel(Order $order): Order
    {
        return $this->transition($order, OrderStatus::Cancelled);
    }

    public function expire(Order $order): Order
    {
        return $this->transition($order, OrderStatus::Expired);
    }

    private function transition(
        Order $order,
        OrderStatus $target,
    ): Order {
        return DB::transaction(function () use ($order, $target) {
            $order = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // Repeated requests must not restore stock twice.
            if ($order->status === $target) {
                return $order;
            }

            if ($order->status !== OrderStatus::Pending) {
                throw ValidationException::withMessages([
                    'order' => 'This order cannot be cancelled or expired.',
                ]);
            }

            if (
                $target === OrderStatus::Expired &&
                (
                    $order->expires_at === null ||
                    $order->expires_at->isFuture()
                )
            ) {
                throw ValidationException::withMessages([
                    'order' => 'This order has not expired.',
                ]);
            }

            $this->inventory->release($order);

            $order->status = $target;
            $order->save();

            return $order;
        }, attempts: 3);
    }
}
