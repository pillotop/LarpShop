<?php

namespace App\Services\Reviews;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use App\Queries\Reviews\ProductReviewQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductReviewService
{
    public function __construct(private ProductReviewQuery $reviews) {}

    public function create(User $user, Product $product, array $data): ProductReview
    {
        return DB::transaction(function () use ($user, $product, $data) {
            // Serialize submissions by this customer, including requests from different pages.
            User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            if ($this->reviews->eligibleProductIds($user, [$product->getKey()]) === []) {
                throw ValidationException::withMessages([
                    'eligibility' => 'You can review a product once, after a paid purchase.',
                ])->errorBag('review');
            }

            $review = new ProductReview;
            $review->user_id = $user->getKey();
            $review->product_id = $product->getKey();
            $review->rating = $data['rating'];
            $review->comment = $data['comment'] ?? null;
            $review->save();

            return $review;
        });
    }
}
