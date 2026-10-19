<?php

namespace App\View;

use App\Http\Controllers\Controller;
use App\Queries\Products\ProductCatalogQuery;
use Illuminate\Http\Request;

class ProductSearchController extends Controller
{
    public function __construct(
        private ProductCatalogQuery $productCatalogQuery
    ) {
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $keyword = trim($validated['q'] ?? '');

        $products = $this->productCatalogQuery
            ->searchProducts($keyword);

        return view('products.pages.index', [
            'products' => $products,
            'keyword' => $keyword,
        ]);
    }
}