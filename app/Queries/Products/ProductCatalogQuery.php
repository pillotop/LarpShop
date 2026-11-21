<?php

namespace App\Queries\Products;

use App\Enums\PromoSource;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCollection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    /** Shared eligibility for collection pages and promo section existence checks. */
    public function constrainCollectionVisibility(Builder $query, bool $inStockOnly = false): Builder
    {
        return $query->approved()->active()
            ->whereHas('vendor', fn (Builder $vendor) => $vendor->active())
            ->when($inStockOnly, fn (Builder $products) => $products->where('products.stock', '>', 0));
    }

    public function collectionProducts(ProductCollection $collection, int $limit = 12): Collection
    {
        $max = max(array_column(config('promotions.layouts'), 'product_limit'));

        return $this->collectionProductsQuery($collection, inStockOnly: true)
            ->limit(max(1, min($limit, $max)))->get();
    }

    /** Load a bounded product list per collection in one batch for homepage sections. */
    public function loadPromoProducts(Collection $collections, ?int $limit = null): void
    {
        $limit ??= max(array_column(config('promotions.layouts'), 'product_limit'));
        $collections->load(['products' => function (BelongsToMany $products) use ($limit) {
            $this->constrainCollectionVisibility($products->getQuery(), inStockOnly: true)
                ->with(['category', 'images', 'vendor'])
                ->withAvg('reviews as average_rating', 'rating')
                ->withCount('reviews as total_reviews');
            $products->orderByPivot('sort_order')->orderBy('products.product_id')->limit($limit);
        }]);
    }

    /** Shared eligibility query for both source existence checks and product loading. */
    public function automaticPromoCandidates(PromoSource $source): Builder
    {
        if ($source === PromoSource::Collection) {
            throw new \InvalidArgumentException('Collection products require a collection.');
        }
        $query = $this->constrainCollectionVisibility(Product::query(), inStockOnly: true);
        if ($source === PromoSource::TopRated) {
            $query->has('reviews', '>=', max(1, (int) config('promotions.broad_select.minimum_reviews')));
        }

        return $query;
    }

    public function automaticPromoProducts(PromoSource $source, int $limit = 12): Collection
    {
        $query = $this->automaticPromoCandidates($source)
            ->with(['category', 'images', 'vendor'])
            ->withAvg('reviews as average_rating', 'rating')
            ->withCount('reviews as total_reviews');
        if ($source === PromoSource::TopRated) {
            $query->orderByDesc('average_rating')->orderByDesc('total_reviews');
        } else {
            $query->orderByDesc('products.created_at');
        }
        $max = max(1, (int) config('promotions.broad_select.product_limit'));

        return $query->orderByDesc('products.product_id')->limit(max(1, min($limit, $max)))->get();
    }

    public function paginateCollectionProducts(ProductCollection $collection): LengthAwarePaginator
    {
        return $this->collectionProductsQuery($collection)
            ->paginate(config('promotions.collection_page_size'));
    }

    public function collectionCandidates(ProductCollection $collection, string $search = ''): LengthAwarePaginator
    {
        return $this->constrainCollectionVisibility(Product::query())
            ->whereNotIn('products.product_id', $collection->products()->select('products.product_id'))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('products.name', 'like', '%'.$search.'%')->orWhereHas('vendor',
                    fn (Builder $vendor) => $vendor->where('store_name', 'like', '%'.$search.'%'))))
            ->with('vendor')->orderBy('products.name')->orderBy('products.product_id')
            ->paginate(12)->withQueryString();
    }

    public function collectionCandidate(int $productId): ?Product
    {
        return $this->constrainCollectionVisibility(Product::query())->find($productId);
    }

    private function collectionProductsQuery(ProductCollection $collection, bool $inStockOnly = false): Builder
    {
        // Publication/access is checked by the collection reader or authorized preview flow.
        return $this->constrainCollectionVisibility(Product::query(), $inStockOnly)
            ->join('product_collection_items as membership', 'membership.product_id', '=', 'products.product_id')
            ->where('membership.product_collection_id', $collection->getKey())
            ->select('products.*')
            ->with(['category', 'images', 'vendor'])
            ->withAvg('reviews as average_rating', 'rating')
            ->withCount('reviews as total_reviews')
            ->orderBy('membership.sort_order')->orderBy('products.product_id');
    }

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
        $query = Product::query()->approved()->active()->with(['images', 'vendor', 'reviews']);

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
            ->active()
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
            ->active()
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
            ->active()
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
