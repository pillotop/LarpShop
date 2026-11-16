<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductReviewRequest;
use App\Models\Product;
use App\Queries\Reviews\ProductReviewQuery;
use App\Services\Reviews\ProductReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductReviewController extends Controller
{
    public function index(Request $request, Product $product, ProductReviewQuery $reviews): View
    {
        abort_unless($product->status === Product::STATUS_APPROVED, 404);
        $validated = $request->validate([
            'reviews_page' => ['nullable', 'integer', 'min:1'],
            'reviews_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'reviews_commented' => ['nullable', 'boolean'],
        ]);

        $ratingFilter = isset($validated['reviews_rating']) ? (int) $validated['reviews_rating'] : null;
        $commentedOnly = $request->boolean('reviews_commented');
        $canReplyToReviews = $request->user()?->vendors()
            ->active()
            ->where('vendor_id', $product->vendor_id)
            ->exists() ?? false;

        return view('products.partials.review-list', [
            'product' => $product,
            'reviews' => $reviews->forProduct($product, $ratingFilter, $commentedOnly),
            'reviewFilters' => [
                'rating' => $ratingFilter,
                'commented' => $commentedOnly,
            ],
            'canReplyToReviews' => $canReplyToReviews,
        ]);
    }

    public function store(StoreProductReviewRequest $request, Product $product, ProductReviewService $reviews): RedirectResponse
    {
        abort_unless($product->status === Product::STATUS_APPROVED, 404);
        $reviews->create($request->user(), $product, $request->validated());

        return back()->withFragment('review-feedback')
            ->with('review_success', __('reviews.success'));
    }
}
