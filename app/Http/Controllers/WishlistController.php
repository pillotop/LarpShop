<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Wishlist\WishlistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function store(Request $request, Product $product, WishlistService $wishlist): RedirectResponse
    {
        $wishlist->add($request->user(), $product);

        return back()->with('status', 'wishlist-updated')
            ->with('success', __('wishlist.saved'));
    }

    public function destroy(Request $request, Product $product, WishlistService $wishlist): RedirectResponse
    {
        $wishlist->remove($request->user(), $product);

        return back()->with('status', 'wishlist-updated')
            ->with('success', __('wishlist.removed'));
    }
}
