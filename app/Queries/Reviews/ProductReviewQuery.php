<?php

namespace App\Queries\Reviews;

use App\Enums\OrderStatus;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductReviewQuery
{
    public function forProduct(Product $product): LengthAwarePaginator
    {
        return $product->reviews()
            ->with('user:user_id,username')
            ->withExists(['user as verified_purchase' => function ($users) {
                $users->whereHas('orders', function ($orders) {
                    $orders->where('status', OrderStatus::Paid->value)
                        ->whereHas('details', fn ($details) => $details
                            ->whereColumn('order_details.product_id', 'product_reviews.product_id'));
                });
            }])
            ->latest('created_at')
            ->orderByDesc('review_id')
            ->paginate(5, ['product_reviews.*'], 'reviews_page');
    }

    public function eligibleProductIds(?User $user, array $productIds): array
    {
        if (! $user || $productIds === []) {
            return [];
        }

        return OrderDetail::query()
            ->whereIn('product_id', $productIds)
            ->whereHas('order', fn ($orders) => $orders
                ->where('user_id', $user->getKey())
                ->where('status', OrderStatus::Paid->value))
            ->whereHas('product', fn ($products) => $products->approved()
                ->whereDoesntHave('reviews', fn ($reviews) => $reviews
                    ->where('user_id', $user->getKey())))
            ->distinct()
            ->pluck('product_id')
            ->all();
    }
}
