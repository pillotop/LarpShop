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
        $request->validate(['reviews_page' => ['nullable', 'integer', 'min:1']]);

        return view('products.partials.review-list', [
            'product' => $product,
            'reviews' => $reviews->forProduct($product),
        ]);
    }

    public function store(StoreProductReviewRequest $request, Product $product, ProductReviewService $reviews): RedirectResponse
    {
        abort_unless($product->status === Product::STATUS_APPROVED, 404);
        $reviews->create($request->user(), $product, $request->validated());

        return back()->withFragment('review-feedback')
            ->with('review_success', 'Thank you! Your review has been published.');
    }
}
