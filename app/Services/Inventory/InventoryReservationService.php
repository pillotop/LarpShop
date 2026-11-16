<?php

namespace App\Services\Inventory;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Validation\ValidationException;
use LogicException;

// this isnt a standalone service, as in it cannot justify for the sake of its
// name if used alone. Without a caller to perform database lock, this service
// is effectively useless. In short: used by OrderCreationService
class InventoryReservationService
{
    /**
     * The caller must hold a database row lock on this product (and optional variant)
     * and execute this inside the order creation transaction.
     */
    public function reserve(Product $product, int $quantity, ?ProductVariant $variant = null): void
    {
        if ($quantity <= 0) {
            throw new LogicException('Invalid reservation quantity.');
        }

        if ($variant !== null) {
            if ($variant->stock < $quantity) {
                throw ValidationException::withMessages([
                    'cart' => __('checkout.errors.insufficient_stock', ['name' => "{$product->name} ({$variant->name})"]),
                ]);
            }

            $variant->decrement('stock', $quantity);

            return;
        }

        if ($product->stock < $quantity) {
            throw ValidationException::withMessages([
                'cart' => __('checkout.errors.insufficient_stock', ['name' => $product->name]),
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
            ->whereNull('variant_id')
            ->pluck('product_id')
            ->unique()
            ->values();

        // Lock products in a deterministic order.
        $products = $productIds->isNotEmpty()
            ? Product::query()
                ->whereIn('product_id', $productIds)
                ->orderBy('product_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id')
            : collect();

        $variantIds = $details
            ->pluck('variant_id')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $variants = $variantIds->isNotEmpty()
            ? ProductVariant::query()
                ->whereIn('variant_id', $variantIds)
                ->orderBy('variant_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('variant_id')
            : collect();

        foreach ($details as $detail) {
            if ($detail->variant_id !== null) {
                $variant = $variants->get($detail->variant_id);
                if ($variant !== null) {
                    $variant->increment('stock', $detail->quantity);
                }

                continue;
            }

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
