<?php

namespace App\Services\Cart;

use App\Queries\Cart\CartQuery;
use App\Support\Carts\CartLock;
use Illuminate\Support\Str;

class CartRetrievalService_
{
    public function __construct(
        private CartQuery $cartQuery,
        private CartLock $cartLock,
    ) {}
}

class CartRetrievalService
{
    public function __construct(
        private CartQuery $cartQuery,
    ) {}

    public function getCartData(
        string $sortBy = 'created_at',
        string $direction = 'asc',
    ): array {
        $cart = $this->cartQuery->getOrInitCart();

        $products = $cart->items->map(function ($cartItem): array {
            $product = $cartItem->itemable;

            if (! $product) {
                return [];
            }

            $image = $product->images->first();
            $path = $image?->path;
            $imageUrl = null;
            if ($path) {
                $imageUrl = Str::startsWith($path, ['http://', 'https://'])
                    ? $path
                    : asset('storage/'.preg_replace('#^storage/#', '', ltrim($path, '/')));
            }

            return [
                'added_at' => $cartItem->created_at?->getTimestamp() ?? $cartItem->cart_item_id,
                'cart_item_id' => $cartItem->cart_item_id,
                'quantity' => $cartItem->quantity,
                'product' => [
                    'product_id' => $product->product_id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'stock' => $product->stock,
                    'image' => $path,
                    'image_url' => $imageUrl,
                    'image_alt' => $image?->alt_text ?: $product->name,
                    'description' => $product->description,
                    'url' => route('products.show', ['product' => $product->product_id, 'slug' => $product->slug]),
                ],
                'subtotal' => $cartItem->quantity * $product->price,
            ];
        })->filter()->values();

        $products = match ($sortBy) {
            default => $products,
            'name' => $products->sortBy('product.name', SORT_STRING, $direction === 'desc'),
            'price' => $products->sortBy('product.price', SORT_NUMERIC, $direction === 'desc'),
            'stock' => $products->sortBy('product.stock', SORT_NUMERIC, $direction === 'desc'),
            'quantity' => $products->sortBy('quantity', SORT_NUMERIC, $direction === 'desc'),
            'subtotal' => $products->sortBy('subtotal', SORT_NUMERIC, $direction === 'desc'),
        };

        return [
            'total_count' => $cart->items->sum('quantity'),
            'products' => $products->values(),
            'total_amount' => number_format($products->sum('subtotal'), 2, '.', ''),
        ];
    }
}
