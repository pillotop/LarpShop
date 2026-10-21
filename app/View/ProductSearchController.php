<?php

namespace App\View;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Queries\Products\ProductCatalogQuery;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductSearchController extends Controller
{
    public function __construct(
        private ProductCatalogQuery $productCatalogQuery
    ) {}

    public function index(Request $request)
    {
        $rules = [
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'in_stock' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'string', Rule::in(array_keys(ProductCatalogQuery::SORT_OPTIONS))],
        ];

        if ($request->filled('min_price') && is_numeric($request->input('min_price'))) {
            $rules['max_price'][] = 'gte:min_price';
        }

        $validated = $request->validate($rules);

        $keyword = trim($validated['q'] ?? '');

        $filters = [
            'category' => isset($validated['category']) ? trim($validated['category']) : '',
            'min_price' => $validated['min_price'] ?? null,
            'max_price' => $validated['max_price'] ?? null,
            'in_stock' => $request->boolean('in_stock'),
            'sort' => $validated['sort'] ?? 'newest',
        ];

        $products = $this->productCatalogQuery
            ->searchProducts($keyword, $filters);

        $productCategories = Category::query()->orderBy('name')->get();
        $selectedCategory = $filters['category'] !== ''
            ? $productCategories->first(fn (Category $cat) => $cat->slug === $filters['category'] || (string) $cat->category_id === $filters['category'])
            : null;

        return view('products.pages.index', [
            'products' => $products,
            'keyword' => $keyword,
            'filters' => $filters,
            'sortOptions' => ProductCatalogQuery::SORT_OPTIONS,
            'selectedCategory' => $selectedCategory,
            'productCategories' => $productCategories,
        ]);
    }
}
