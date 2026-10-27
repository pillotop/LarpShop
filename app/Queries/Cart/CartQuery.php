<?php

namespace App\Queries\Cart;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use App\Support\Carts\CartLock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class CartQuery
{
    public function __construct(
        private CartLock $cartLock,
    ) {}

    public function currentOwner(): array
    {
        if (Auth::check()) {
            return ['user_id' => Auth::id()];
        }

        return ['session_id' => Session::getId()];
    }

    public function findForOwner(array $owner): ?Cart
    {
        return Cart::query()
            ->where($owner)
            ->first();
    }

    public function getOrCreateForOwner(array $owner): Cart
    {
        return Cart::query()->firstOrCreate($owner);
    }

    public function getOrInitCart(): Cart
    {
        $owner = $this->currentOwner();

        return $this->cartLock->forOwner($owner, function () use ($owner) {
            return $this->getOrCreateForOwner($owner)
                        ->load('items.itemable.images');
        });
    }

    public function forUser(User $user): ?Cart
    {
        return $this->findForOwner([
            'user_id' => $user->user_id
        ]);
    }

    public function getForCheckout(User $user): ?Cart
    {
        return Cart::query()
            ->where('user_id', $user->user_id)
            ->lockForUpdate()
            ->first();
    }
}

// class CartQuery
// {
//     private function owner(): array
//     {
//         return Auth::id() ? ['user_id' => Auth::id()] : ['session_id' => Session::id()];
//     }
//
//     private function lockKey(array $owner): string
//     {
//         return 'cart-owner:'.hash('sha256', json_encode($owner));
//     }
//
//     public function getOrInitCart(): Cart
//     {
//         $owner = $this->owner();
//
//         return Cache::lock($this->lockKey($owner), 30)
//             ->block(5, fn () => Cart::with(
//                 ['items.itemable.images'],
//             )->firstOrCreate($owner));
//     }
//
//     public function updateQuantity(int $productId, int $quantity, bool $add = false): array
//     {
//         $owner = $this->owner();
//
//         // Serialize initialization and writes, including the first item in a new cart.
//         return Cache::lock($this->lockKey($owner), 30)->block(5, function () use ($owner, $productId, $quantity, $add) {
//             return DB::transaction(function () use ($owner, $productId, $quantity, $add) {
//                 $product = Product::query()->lockForUpdate()->findOrFail($productId);
//                 $cart = Cart::firstOrCreate($owner);
//                 $item = $cart->items()
//                     ->where('itemable_type', $product->getMorphClass())
//                     ->where('itemable_id', $productId)
//                     ->lockForUpdate()->first();
//                 $nextQuantity = $add ? ($item?->quantity ?? 0) + $quantity : $quantity;
//
//                 if ($nextQuantity > $product->stock || $nextQuantity > 2147483647) {
//                     throw ValidationException::withMessages([
//                         'quantity' => 'Requested quantity exceeds available stock.',
//                     ]);
//                 }
//
//                 if ($nextQuantity === 0) {
//                     $item?->delete();
//                 } elseif ($item) {
//                     $item->update(['quantity' => $nextQuantity]);
//                 } else {
//                     $cart->items()->create([
//                         'itemable_type' => $product->getMorphClass(),
//                         'itemable_id' => $productId,
//                         'quantity' => $nextQuantity,
//                     ]);
//                 }
//
//                 return [
//                     'product_id' => $productId,
//                     'item_quantity' => $nextQuantity,
//                     'total_count' => (int) $cart->items()->sum('quantity'),
//                 ];
//             });
//         });
//     }
// }
