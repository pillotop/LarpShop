<?php

namespace App\View;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Queries\Products\ProductCatalogQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductSearchController extends Controller
{
    public function __construct(
        private ProductCatalogQuery $productCatalogQuery
    ) {}

    public function suggestions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $keyword = trim($validated['q'] ?? '');
        if (mb_strlen($keyword) < 2) {
            return response()->json(['items' => []]);
        }

        $items = $this->productCatalogQuery
            ->suggestProducts($keyword, 6)
            ->map(function ($product): array {
                $firstPath = $product->images->first()?->path;
                $imageUrl = $firstPath
                    ? (Str::startsWith($firstPath, ['http://', 'https://'])
                        ? $firstPath
                        : asset('storage/'.ltrim($firstPath, '/')))
                    : asset('images/product-placeholder.jpg');

                return [
                    'id' => $product->product_id,
                    'name' => $product->name,
                    'price' => '$'.number_format((float) $product->price, 2),
                    'category' => $product->category?->name ?? '',
                    'image_url' => $imageUrl,
                    'url' => route('products.show', [
                        'product' => $product->product_id,
                        'slug' => $product->slug,
                    ]),
                ];
            })
            ->values();

        return response()->json(['items' => $items]);
    }

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
