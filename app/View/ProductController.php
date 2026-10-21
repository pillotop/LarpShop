<?php

namespace App\View;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\Products\ProductRetrievalService;

class ProductController extends Controller
{
    public function __construct(private ProductRetrievalService $productRetrievalService) {}

    public function show(Product $product, ?string $slug = null)
    {
        if ($slug !== $product->slug) {
            return redirect()->route('products.show', [
                'product' => $product->product_id,
                'slug' => $product->slug,
            ]);
        }

        $service = $this->productRetrievalService;
        $productDetails = $service->getProductDetails($product->product_id);

        if (! $productDetails) {
            abort(404);
        }

        return view('products.pages.show', [
            'product' => $productDetails,
            'similarProducts' => $service->getSimilarProducts(
                productId: $product->product_id,
                categoryId: $product->category_id,
                limit: 6,
            ),
            'productCategories' => Category::query()->get(),
        ]);
    }
}
