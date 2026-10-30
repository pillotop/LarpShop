<?php

namespace App\View\Composers;

use App\Queries\Wishlist\WishlistQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistButtonComposer
{
    public function __construct(private Request $request, private WishlistQuery $wishlist) {}

    public function compose(View $view): void
    {
        // Share one lookup across every wishlist button rendered in this request.
        $ids = $this->request->attributes->get('wishlist_product_ids');

        if ($ids === null) {
            $user = $this->request->user();
            $ids = $user ? $this->wishlist->productIds($user) : [];
            $this->request->attributes->set('wishlist_product_ids', $ids);
        }

        $view->with('wishlistProductIds', $ids);
    }
}
