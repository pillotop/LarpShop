<?php

namespace App\Services\Wishlist;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class WishlistService
{
    public function add(User $user, Product $product): void
    {
        abort_unless($product->status === Product::STATUS_APPROVED, 404);

        // The composite primary key makes repeated and concurrent adds idempotent.
        DB::table('wishlist_items')->insertOrIgnore([
            'user_id' => $user->getKey(),
            'product_id' => $product->getKey(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function remove(User $user, Product $product): void
    {
        $user->wishlistProducts()->detach($product->getKey());
    }
}
