<?php

namespace App\Services\Cart;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Queries\Cart\CartQuery;
use App\Support\Carts\CartLock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartMutationService
{
    public function __construct(
        private CartQuery $carts,
        private CartLock $cartLock
    ) {}

    public function updateQuantity(
        int $productId,
        int $quantity,
        bool $add = false,
        ?int $variantId = null,
    ): array {

        $owner = $this->carts->currentOwner();

        return $this->cartLock->forOwner(
            $owner,
            fn () => DB::transaction(
                fn () => $this->updateQuantityLocked(
                    $owner,
                    $productId,
                    $quantity,
                    $add,
                    $variantId,
                ),
                attempts: 3,
            )
        );
    }

    private function updateQuantityLocked(
        array $owner,
        int $productId,
        int $quantity,
        bool $add,
        ?int $variantId = null,
    ): array {
        if ($quantity < 0) {
            throw ValidationException::withMessages([
                'quantity' => __('cart.errors.negative'),
            ]);
        }

        $product = Product::query()
            ->lockForUpdate()
            ->findOrFail($productId);

        $variant = null;
        if ($variantId !== null) {
            $variant = ProductVariant::query()
                ->where('product_id', $product->getKey())
                ->where('is_active', true)
                ->lockForUpdate()
                ->find($variantId);

            if (! $variant) {
                throw ValidationException::withMessages([
                    'variant_id' => __('cart.errors.invalid_variant'),
                ]);
            }
        }

        $cart = $this->carts->getOrCreateForOwner($owner);

        $item = $cart->items()
            ->where('itemable_type', $product->getMorphClass())
            ->where('itemable_id', $product->getKey())
            ->when(
                $variant !== null,
                fn ($q) => $q->where('variant_id', $variant->getKey()),
                fn ($q) => $q->whereNull('variant_id'),
            )
            ->lockForUpdate()
            ->first();

        $availableStock = $variant ? $variant->stock : $product->stock;
        $nextQuantity = $add
            ? ($item?->quantity ?? 0) + $quantity
            : $quantity;

        if (
            $nextQuantity > $availableStock ||
            $nextQuantity > 2147483647
        ) {
            throw ValidationException::withMessages([
                'quantity' => __('cart.errors.stock'),
            ]);
        }

        if ($nextQuantity === 0) {
            $item?->delete();
        } elseif ($item) {
            $item->update([
                'quantity' => $nextQuantity,
            ]);
        } else {
            $cart->items()->create([
                'itemable_type' => $product->getMorphClass(),
                'itemable_id' => $product->getKey(),
                'variant_id' => $variant?->getKey(),
                'quantity' => $nextQuantity,
            ]);
        }

        return [
            'product_id' => $product->getKey(),
            'variant_id' => $variant?->getKey(),
            'quantity' => $nextQuantity,
            'total_count' => (int) $cart->items()->sum('quantity'),
        ];
    }
}
