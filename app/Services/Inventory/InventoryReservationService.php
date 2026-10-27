<?php

namespace App\Services\Inventory;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Validation\ValidationException;
use LogicException;

// this isnt a standalone service, as in it cannot justify for the sake of its
// name if used alone. Without a caller to perform database lock, this service
// is effectively useless. In short: used by OrderCreationService
class InventoryReservationService
{
    /**
     * The caller must hold a database row lock on this product
     * and execute this inside the order creation transaction.
     */
    public function reserve(Product $product, int $quantity): void
    {
        if ($quantity <= 0) {
            throw new LogicException('Invalid reservation quantity.');
        }

        if ($product->stock < $quantity) {
            throw ValidationException::withMessages([
                'cart' => "Insufficient stock for {$product->name}.",
            ]);
        }

        $product->decrement('stock', $quantity);
    }

    /**
     * The caller must lock the order and execute this in
     * the same transaction as its status transition.
     */
    public function release(Order $order): void
    {
        if ($order->stock_released_at !== null) {
            throw new LogicException('Order stock was already released.');
        }

        $details = $order->details()
            ->orderBy('product_id')
            ->get();

        $productIds = $details
            ->pluck('product_id')
            ->unique()
            ->values();

        // Lock products in a deterministic order.
        $products = Product::query()
            ->whereIn('product_id', $productIds)
            ->orderBy('product_id')
            ->lockForUpdate()
            ->get()
            ->keyBy('product_id');

        foreach ($details as $detail) {
            $product = $products->get($detail->product_id);

            if ($product === null) {
                throw new LogicException(
                    "Missing product {$detail->product_id}."
                );
            }

            $product->increment('stock', $detail->quantity);
        }

        $order->stock_released_at = now();
        $order->save();
    }
}
