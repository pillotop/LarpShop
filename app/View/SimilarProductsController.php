<?php

namespace App\View;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Products\ProductRetrievalService;
use Illuminate\Http\Response;

class SimilarProductsController extends Controller
{
    public function __invoke(Product $product, ProductRetrievalService $products): Response
    {
        abort_unless($products->getProductDetails($product->getKey()), 404);

        return response()->view('products.partials.similar-products', [
            'similarProducts' => $products->getSimilarProducts(
                productId: $product->getKey(),
                categoryId: $product->category_id,
                limit: 6,
            ),
        ])->header('Cache-Control', 'private, no-store');
    }
}
