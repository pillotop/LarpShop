<?php

namespace App\Services\Promotions;

use App\Models\ProductCollection;
use App\Queries\Products\ProductCatalogQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductCollectionManagementService
{
    public function __construct(private ProductCatalogQuery $products) {}

    public function save(?ProductCollection $collection, array $attributes): ProductCollection
    {
        // The caller supplies only validated collection metadata.
        return DB::transaction(function () use ($collection, $attributes) {
            $collection = $collection ? $this->locked($collection) : new ProductCollection;
            $collection->fill($attributes)->save();

            return $collection;
        });
    }

    public function addProduct(ProductCollection $collection, int $productId): void
    {
        DB::transaction(function () use ($collection, $productId) {
            $collection = $this->locked($collection);
            if ($collection->products()->whereKey($productId)->exists()) {
                return;
            }
            if (! $this->products->collectionCandidate($productId)) {
                throw ValidationException::withMessages(['product_id' => __('promotions.product_unavailable')]);
            }
            if ($collection->products()->count() >= config('promotions.max_collection_products')) {
                throw ValidationException::withMessages(['product_id' => __('promotions.product_limit', [
                    'count' => config('promotions.max_collection_products'),
                ])]);
            }

            $position = (int) $collection->products()->max('product_collection_items.sort_order') + 1;
            $collection->products()->attach($productId, ['sort_order' => $position]);
            $collection->touch();
        });
    }

    public function removeProduct(ProductCollection $collection, int $productId): void
    {
        DB::transaction(function () use ($collection, $productId) {
            $collection = $this->locked($collection);
            abort_unless($collection->products()->whereKey($productId)->exists(), 404);
            $collection->products()->detach($productId);
            $collection->touch();
        });
    }

    public function moveProduct(ProductCollection $collection, int $productId, string $direction): void
    {
        DB::transaction(function () use ($collection, $productId, $direction) {
            $collection = $this->locked($collection);
            $ids = $collection->products()->orderByPivot('sort_order')->orderBy('products.product_id')
                ->pluck('products.product_id')->all();
            $position = array_search($productId, $ids, true);
            abort_if($position === false, 404);
            $neighbor = $position + ($direction === 'up' ? -1 : 1);
            if (! isset($ids[$neighbor])) {
                return;
            }

            [$ids[$position], $ids[$neighbor]] = [$ids[$neighbor], $ids[$position]];
            foreach ($ids as $order => $id) {
                $collection->products()->updateExistingPivot($id, ['sort_order' => $order]);
            }
            $collection->touch();
        });
    }

    public function delete(ProductCollection $collection): void
    {
        DB::transaction(function () use ($collection) {
            $collection = $this->locked($collection);
            if ($collection->sections()->exists()) {
                throw ValidationException::withMessages(['collection' => __('promotions.collection_in_use')]);
            }
            $collection->delete();
        });
    }

    private function locked(ProductCollection $collection): ProductCollection
    {
        return ProductCollection::query()->lockForUpdate()->findOrFail($collection->getKey());
    }
}
