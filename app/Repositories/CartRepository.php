<?php

namespace App\Repositories;

use App\Models\Cart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartRepository
{
    public function __construct()
    {
    }

    public function request(): array
    {
        $userId = Auth::id();
        $sessionId = Session::id();

        $cart = Cart::with(['items.itemable.images'])
            ->firstOrCreate($userId
                ? ['user_id' => $userId]
                : ['session_id' => $sessionId]);

        $items = $cart->items->map(function ($cartItem) {
            $product = $cartItem->itemable;

            if (!$product) {
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
