<?php

namespace App\Services\Cart;

use App\Queries\Cart\CartQuery;

class CartRetrievalService
{
    public function __construct(
        private CartQuery $cartQuery
    ) {}

    public function getCartData(): array
    {
        $cart = $this->cartQuery->getCurrentCart();

        $items = $cart->items->map(function ($cartItem) {
            $product = $cartItem->itemable;

            if (! $product) {
                return null;
            }

            return [
                'cart_item_id' => $cartItem->cart_item_id,
                'quantity' => $cartItem->quantity,
                'product' => [
                    'product_id' => $product->product_id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'stock' => $product->stock,
                    'image' => $product->images->first()?->path,
                ],
                'subtotal' => $cartItem->quantity * $product->price,
            ];
        })->filter()->values();

        return [
            'total_count' => $cart->items->sum('quantity'),
            'items' => $items,
        ];
    }
}
