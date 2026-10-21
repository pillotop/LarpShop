<?php

namespace App\Queries\Products;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductCatalogQuery
{
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
        $query = Product::query()->with(['images', 'vendor', 'reviews']);

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
            ->with(['images', 'vendor', 'reviews'])
            ->withAvg('reviews as average_rating', 'rating')
            ->withCount('reviews as total_reviews')
            ->where('product_id', $productId)
            ->first();
    }

    public function getSimilarProducts(int $productId): ?Collection
    {
        return Product::query()
            ->with(['images', 'vendor', 'reviews'])
            ->withAvg('reviews as average_rating', 'rating')
            ->withCount('reviews as total_reviews')
            ->whereKeyNot($productId)
            ->get();
    }

    public function searchProducts(?string $keyword = null): Collection
    {
        $query = Product::query()
            ->with(['category', 'images'])
            ->withAvg('reviews as average_rating', 'rating')
            ->withCount('reviews as total_reviews')
            ->orderByDesc('created_at')
            ->orderByDesc('product_id');

        if ($keyword !== null && $keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%'.$keyword.'%')
                    ->orWhere('description', 'like', '%'.$keyword.'%');
            });
        }

        return $query->limit(100)->get();
    }
}
