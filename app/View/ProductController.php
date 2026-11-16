<?php

namespace App\View;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Queries\Reviews\ProductReviewQuery;
use App\Services\Products\ProductRetrievalService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private ProductRetrievalService $productRetrievalService) {}

    public function show(Request $request, ProductReviewQuery $reviews, Product $product, ?string $slug = null)
    {
        if ($slug !== $product->slug) {
            return redirect()->route('products.show', [
                'product' => $product->product_id,
                'slug' => $product->slug,
            ]);
        }

        $validated = $request->validate([
            'reviews_page' => ['nullable', 'integer', 'min:1'],
            'reviews_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'reviews_commented' => ['nullable', 'boolean'],
        ]);

        $service = $this->productRetrievalService;
        $productDetails = $service->getProductDetails($product->product_id);

        if (! $productDetails) {
            abort(404);
        }

        $ratingFilter = isset($validated['reviews_rating']) ? (int) $validated['reviews_rating'] : null;
        $commentedOnly = $request->boolean('reviews_commented');
        $canReplyToReviews = $request->user()?->vendors()
            ->active()
            ->where('vendor_id', $productDetails->vendor_id)
            ->exists() ?? false;

        return view('products.pages.show', [
            'product' => $productDetails,
            'reviews' => $reviews->forProduct($productDetails, $ratingFilter, $commentedOnly),
            'ratingBreakdown' => $reviews->ratingBreakdown($productDetails),
            'reviewFilters' => [
                'rating' => $ratingFilter,
                'commented' => $commentedOnly,
            ],
            'canReplyToReviews' => $canReplyToReviews,
            'canReview' => $reviews->eligibleProductIds($request->user(), [$product->getKey()]) !== [],
            'similarProducts' => $service->getSimilarProducts(
                productId: $product->product_id,
                categoryId: $product->category_id,
                limit: 6,
            ),
            'productCategories' => Category::query()->get(),
        ]);
    }
}
