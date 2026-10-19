<?php

namespace App\View;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Products\ProductRetrievalService;
use App\Models\Category;

class ProductController extends Controller
{
    public function __construct(private ProductRetrievalService $productRetrievalService)
    {
    }

    public function show(Product $product, ?string $slug = null)
    {
        if ($slug !== $product->slug) {
            return redirect()->route('products.show', [
                'product' => $product->product_id,
                'slug' => $product->slug
            ]);
        }

        $service = $this->productRetrievalService;

        return view('products.pages.show', [
            'product' => $service->getProductDetails($product->product_id),
            'similarProducts' => $service->getSimilarProducts($product->product_id),
            'productCategories' => Category::query()->get(),
        ]);
    }
}
