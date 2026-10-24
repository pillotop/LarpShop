<?php

namespace App\Queries\Products;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductCatalogQuery
{
    public const SORT_OPTIONS = [
        'newest' => 'Newest Arrivals',
        'price_asc' => 'Price: Low to High',
        'price_desc' => 'Price: High to Low',
        'name_asc' => 'Name: A to Z',
        'name_desc' => 'Name: Z to A',
        'rating_desc' => 'Highest Rated',
    ];

    private function getAllCategoryIds(int $categoryId): array
    {
        $category = Category::with('children')->find($categoryId);

        if (! $category) {
            return [$categoryId];
        }

        $ids = [$category->category_id];

        foreach ($category->children as $child) {
            $ids = array_merge($ids, $this->getAllCategoryIds($child->category_id));
        }

        return $ids;
    }

    public function getProducts(
        ?int $categoryId = null,
        int $limit = 12,
        bool $inStockOnly = true,
    ): Collection {
        $query = Product::query()->approved()->with(['images', 'vendor', 'reviews']);

        if ($categoryId) {
            // Recursively get the category ID and all its descendant IDs
            $categoryIds = $this->getAllCategoryIds($categoryId);
            $query->whereIn('category_id', $categoryIds);
        }

        if ($inStockOnly) {
            $query->where('stock', '>', 0);
        }

        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('product_id')
            ->withAvg('reviews as average_rating', 'rating')
            ->withCount('reviews as total_reviews')
            ->limit(max(1, $limit))
            ->get();
    }

    public function getProductDetails(int $productId): ?Product
    {
        return Product::query()
            ->approved()
            ->with(['images', 'vendor', 'reviews', 'category'])
            ->withAvg('reviews as average_rating', 'rating')
            ->withCount('reviews as total_reviews')
            ->where('product_id', $productId)
            ->first();
    }

    public function getSimilarProducts(int $productId, ?int $categoryId = null, int $limit = 6): Collection
    {
        $query = Product::query()
            ->approved()
            ->with(['images', 'vendor', 'reviews', 'category'])
            ->withAvg('reviews as average_rating', 'rating')
            ->withCount('reviews as total_reviews')
            ->whereKeyNot($productId)
            ->where('stock', '>', 0);

        if ($categoryId !== null) {
            $categoryIds = $this->getAllCategoryIds($categoryId);
            $query->whereIn('category_id', $categoryIds);
        }

        return $query->limit($limit)->get();
    }

    public function searchProducts(?string $keyword = null, array $filters = []): Collection
    {
        $query = Product::query()
            ->approved()
            ->with(['category', 'images'])
            ->withAvg('reviews as average_rating', 'rating')
            ->withCount('reviews as total_reviews');

        if ($keyword !== null && $keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%'.$keyword.'%')
                    ->orWhere('description', 'like', '%'.$keyword.'%');
            });
        }

        $categoryFilter = isset($filters['category']) ? trim((string) $filters['category']) : '';
        if ($categoryFilter !== '') {
            $category = Category::query()
                ->where('slug', $categoryFilter)
                ->when(ctype_digit($categoryFilter), fn ($q) => $q->orWhere('category_id', (int) $categoryFilter))
                ->first();

            if ($category) {
                $categoryIds = $this->getAllCategoryIds($category->category_id);
                $query->whereIn('category_id', $categoryIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if (isset($filters['min_price']) && $filters['min_price'] !== '' && is_numeric($filters['min_price'])) {
            $query->where('price', '>=', (float) $filters['min_price']);
        }

        if (isset($filters['max_price']) && $filters['max_price'] !== '' && is_numeric($filters['max_price'])) {
            $query->where('price', '<=', (float) $filters['max_price']);
        }

        if (! empty($filters['in_stock'])) {
            $query->where('stock', '>', 0);
        }

        $sort = $filters['sort'] ?? 'newest';

        match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc')->orderBy('product_id', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc')->orderByDesc('product_id'),
            'name_asc' => $query->orderBy('name', 'asc')->orderBy('product_id', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc')->orderByDesc('product_id'),
            'rating_desc' => $query->orderByDesc('average_rating')->orderByDesc('total_reviews')->orderByDesc('product_id'),
            default => $query->orderByDesc('created_at')->orderByDesc('product_id'),
        };

        return $query->limit(100)->get();
    }
}
