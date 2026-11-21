<?php

namespace App\View;

use App\Http\Controllers\Controller;
use App\Queries\Products\ProductCatalogQuery;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class HomeDiscoveryController extends Controller
{
    public function __invoke(Request $request, ProductCatalogQuery $catalog): Response
    {
        $input = $request->validate([
            'category' => ['nullable', 'integer', 'exists:categories,category_id'],
            'expanded' => ['sometimes', 'boolean'],
        ]);
        $expanded = (bool) ($input['expanded'] ?? false);
        $products = $catalog->discoveryProducts(
            isset($input['category']) ? (int) $input['category'] : null,
            $expanded,
        );

        return response()->view('products.partials.home-discovery', [
            'products' => $products->take($expanded ? 24 : 12),
            'expanded' => $expanded,
            'hasMore' => ! $expanded && $products->count() > 12,
        ])->header('Cache-Control', 'private, no-store');
    }
}
