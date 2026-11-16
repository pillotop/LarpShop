<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Queries\Address\AddressQuery;
use App\Queries\Categories\CategoryQuery;
use App\Queries\Orders\PurchaseHistoryQuery;
use App\Queries\Reviews\ProductReviewQuery;
use App\Queries\Vendors\VendorQuery;
use App\Queries\Wishlist\WishlistQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(
        Request $request,
        VendorQuery $vendors,
        CategoryQuery $categories,
        AddressQuery $addresses,
        WishlistQuery $wishlist,
        PurchaseHistoryQuery $purchaseHistory,
        ProductReviewQuery $reviews,
    ): View {
        $user = $request->user();

        $purchaseFilters = $purchaseHistory->normalizeFilters($request->query());
        $purchases = $purchaseHistory->forUser($user, $purchaseFilters);

        $purchasedProductIds = $purchases->getCollection()
            ->flatMap(fn ($order) => $order->details->pluck('product_id'))
            ->unique()
            ->values()
            ->all();

        return view('profile.pages.show', [
            'user' => $user,
            'wishlistProducts' => $wishlist->forUser($user),
            'vendors' => $vendors->forUser($user),
            'addresses' => $addresses->forUser($user),
            'productCategories' => $categories->forNavigation(),
            'purchases' => $purchases,
            'purchaseFilters' => $purchaseFilters,
            'reviewableProductIds' => $reviews->eligibleProductIds($user, $purchasedProductIds),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $user->update([
            'username' => $data['username'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'] ?? null,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => __('profile.success.updated'), 'user' => $user]);
        }

        return redirect()->route('profile')
            ->with('status', 'profile-updated')
            ->with('success', __('profile.success.updated'));
    }
}
