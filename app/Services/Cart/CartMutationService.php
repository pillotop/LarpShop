<?php

namespace App\Services\Cart;

use App\Models\Product;
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
    ): array {
        if ($quantity < 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity cannot be negative.',
            ]);
        }

        $product = Product::query()
            ->lockForUpdate()
            ->findOrFail($productId);

        $cart = $this->carts->getOrCreateForOwner($owner);

        $item = $cart->items()
            ->where('itemable_type', $product->getMorphClass())
            ->where('itemable_id', $product->getKey())
            ->lockForUpdate()
            ->first();

        $nextQuantity = $add
            ? ($item?->quantity ?? 0) + $quantity
            : $quantity;

        if (
            $nextQuantity > $product->stock ||
            $nextQuantity > 2147483647
        ) {
            throw ValidationException::withMessages([
                'quantity' => 'Requested quantity exceeds available stock',
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
                'quantity' => $nextQuantity,
            ]);
        }

        return [
            'product_id' => $product->getKey(),
            'quantity' => $nextQuantity,
            'total_count' => (int) $cart->items()->sum('quantity'),
        ];
    }
}
