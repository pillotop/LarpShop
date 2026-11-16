<?php

namespace App\Queries\Reviews;

use App\Enums\OrderStatus;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductReviewQuery
{
    public function forProduct(Product $product, ?int $rating = null, bool $withCommentOnly = false): LengthAwarePaginator
    {
        $query = $product->reviews()
            ->with(['user:user_id,username', 'product.vendor:vendor_id,store_name'])
            ->withExists(['user as verified_purchase' => function ($users) {
                $users->whereHas('orders', function ($orders) {
                    $orders->where('status', OrderStatus::Paid->value)
                        ->whereHas('details', fn ($details) => $details
                            ->whereColumn('order_details.product_id', 'product_reviews.product_id'));
                });
            }])
            ->when($rating !== null, fn ($q) => $q->where('rating', $rating))
            ->when($withCommentOnly, fn ($q) => $q->whereNotNull('comment')->where('comment', '!=', ''))
            ->latest('created_at')
            ->orderByDesc('review_id')
            ->paginate(5, ['product_reviews.*'], 'reviews_page');

        return $query->appends(array_filter([
            'reviews_rating' => $rating,
            'reviews_commented' => $withCommentOnly ? '1' : null,
        ], fn ($v) => $v !== null));
    }

    /**
     * @return array<int, array{count: int, percent: int}>
     */
    public function ratingBreakdown(Product $product): array
    {
        $rows = $product->reviews()
            ->select('rating', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('rating')
            ->pluck('aggregate', 'rating');

        $total = (int) $rows->sum();
        $breakdown = [];

        for ($star = 5; $star >= 1; $star--) {
            $count = (int) ($rows[$star] ?? 0);
            $breakdown[$star] = [
                'count' => $count,
                'percent' => $total > 0 ? (int) round(($count / $total) * 100) : 0,
            ];
        }

        return $breakdown;
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
