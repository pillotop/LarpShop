<?php

namespace App\Queries\Wishlist;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WishlistQuery
{
    public function forUser(User $user): LengthAwarePaginator
    {
        return $user->wishlistProducts()
            ->approved()
            ->with('images')
            ->orderByPivot('created_at', 'desc')
            ->orderByDesc('products.product_id')
            ->paginate(12, ['products.*'], 'wishlist_page')
            ->fragment('pane-wishlist');
    }

    public function productIds(User $user): array
    {
        return $user->wishlistProducts()->pluck('products.product_id')->all();
    }
}
