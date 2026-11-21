<?php

namespace App\Http\Controllers;

use App\Queries\Products\ProductCatalogQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductSearchSuggestionController extends Controller
{
    public function __invoke(Request $request, ProductCatalogQuery $catalog): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);
        $keyword = trim($validated['q'] ?? '');
        $category = trim($validated['category'] ?? '');

        if (mb_strlen($keyword) < 2) {
            return response()->json(['html' => '', 'count' => 0]);
        }

        $suggestions = $catalog->searchSuggestions($keyword, $category);

        return response()->json([
            'html' => view('products.partials.search-suggestions', [
                ...$suggestions,
                'keyword' => $keyword,
                'category' => $category,
            ])->render(),
            'count' => $suggestions['products']->count() + $suggestions['categories']->count(),
        ])->header('Cache-Control', 'private, no-store');
    }
}
