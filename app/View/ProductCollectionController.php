<?php

namespace App\View;

use App\Http\Controllers\Controller;
use App\Queries\Products\ProductCatalogQuery;
use App\Queries\Promotions\ProductCollectionQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductCollectionController extends Controller
{
    public function show(Request $request, string $slug, ProductCollectionQuery $collections, ProductCatalogQuery $products): View
    {
        $request->validate(['page' => ['nullable', 'integer', 'min:1', 'max:1000000']]);
        $collection = $collections->publishedBySlug($slug);

        return view('collections.pages.show', [
            'collection' => $collection,
            'products' => $products->paginateCollectionProducts($collection),
        ]);
    }
}
