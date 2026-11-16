<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Wishlist\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function store(Request $request, Product $product, WishlistService $wishlist): RedirectResponse|JsonResponse
    {
        $wishlist->add($request->user(), $product);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'saved' => true,
                'message' => __('wishlist.saved'),
                'action_url' => route('wishlist.destroy', $product),
            ]);
        }

        return back()->with('status', 'wishlist-updated')
            ->with('success', __('wishlist.saved'));
    }

    public function destroy(Request $request, Product $product, WishlistService $wishlist): RedirectResponse|JsonResponse
    {
        $wishlist->remove($request->user(), $product);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'saved' => false,
                'message' => __('wishlist.removed'),
                'action_url' => route('wishlist.store', $product),
            ]);
        }

        return back()->with('status', 'wishlist-updated')
            ->with('success', __('wishlist.removed'));
    }
}
