<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;

class CartController extends Controller
{
    public function index(): JsonResponse
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

        return response()->json([
            'success' => true,
            'cart' => [
                'total_count' => $cart->items->sum('quantity'),
                'items' => $items,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'product_id')],
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $quantity = (int) $validated['quantity'];

        $userId = Auth::id();
        $sessionId = Session::id();

        $cart = Cart::firstOrCreate(
            $userId ? ['user_id' => $userId] : ['session_id' => $sessionId]
        );

        $cartItem = CartItem::where('cart_id', $cart->cart_id)
            ->where('itemable_type', Product::class)
            ->where('itemable_id', $product->product_id)
            ->first();

        if ($quantity === 0) {
            if ($cartItem) {
                $cartItem->delete();
            }
        } else {
            if ($product->stock < $quantity) {
                return response()->json([
                    'success' => false,
                    'error' => 'Requested quantity exceeds available stock.',
                ], 422);
            }

            if ($cartItem) {
                $cartItem->update(['quantity' => $quantity]);
            } else {
                CartItem::create([
                    'cart_id' => $cart->cart_id,
                    'itemable_type' => Product::class,
                    'itemable_id' => $product->product_id,
                    'quantity' => $quantity,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Cart updated.',
            'quantity' => $cartItem?->cart->items()->sum('quantity') ?? $cart->items()->sum('quantity'),
        ]);
    }

}
