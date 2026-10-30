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

        $request->validate(['reviews_page' => ['nullable', 'integer', 'min:1']]);

        $service = $this->productRetrievalService;
        $productDetails = $service->getProductDetails($product->product_id);

        if (! $productDetails) {
            abort(404);
        }

        return view('products.pages.show', [
            'product' => $productDetails,
            'reviews' => $reviews->forProduct($productDetails),
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
