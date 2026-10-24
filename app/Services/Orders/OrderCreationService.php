<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Queries\Cart\CartQuery;
use App\Support\Carts\CartLock;
use App\Support\Money\MoneyCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderCreationService
{
    public function __construct(
        private CartQuery $carts,
        private CartLock $cartLock,
        private MoneyCalculator $money,
    ) {}

    public function create(User $user): Order
    {
        $owner = ['user_id' => $user->user_id];

        return $this->cartLock->forOwner(
            $owner,
            fn () => DB::transaction(
                fn () => $this->createLocked($user),
                attempts: 3,
            )
        );
    }

    private function createLocked(User $user): Order
    {
        // Lock the cart belonging to this user.
        $cart = $this->carts->getForCheckout($user);

        if (!$cart) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart is empty',
            ]);
        }

        // Lock its items so checkout works with a stable cart.
        $items = $cart->items()
                     ->orderBy('cart_item_id')
                     ->lockForUpdate()
                     ->get();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart is empty',
            ]);
        }

        $productType = (new Product())->getMorphClass();

        // Only products are purchasable for now.
        foreach ($items as $item) {
            if (
                $item->itemable_type !== $productType ||
                $item->quantity < 1
            ) {
                throw ValidationException::withMessages([
                    'cart' => 'Your cart contains an invalid item.',
                ]);
            }
        }

        // Lock products in a consistent primary-key order.
        $productIds = $items
            ->pluck('itemable_id')
            ->unique()
            ->sort()
            ->values();

        $products = Product::query()
            ->whereIn('product_id', $productIds)
            ->orderBy('product_id')
            ->lockForUpdate()
            ->get()
            ->keyBy('product_id');

        if ($products->count() !== $productIds->count()) {
            throw ValidationException::withMessages([
                'cart' => 'Some products are no longer available',
            ]);
        }



        $total = $this->money->zero();

        // Validate everything before making changes.
        foreach ($items as $item) {
            $product = $products->get($item->itemable_id);

            if ($item->quantity > $product->stock) {
                throw ValidationException::withMessages([
                    'cart' => "Insufficient stock for {$product->name}",
                ]);
            }

            $total = $this->money->add(
                $total,
                $this->money->subtotal(
                    (string) $product->price,
                    (int) $item->quantity,
                ),
            );
        }

        // All checks passed: create an order snapshot.
        $order = Order::create([
            'user_id' => $user->user_id,
            'total_amount' => $this->money->format($total),
            'status' => 'pending',
        ]);

        foreach ($items as $item) {
            $product = $products->get($item->itemable_id);

            $order->details()->create([
                'product_id' => $product->product_id,
                'quantity' => $item->quantity,
                'unit_price' => $product->price,
            ]);

            $product->decrement('stock', $item->quantity);
        }

        // The order now owns its own snapshots of the cart items.
        $cart->items()->delete();
        return $order;
    }
}
