<?php

namespace App\Queries\Promotions;

use App\Models\ProductCollection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ProductCollectionQuery
{
    public function forAdmin(string $search = ''): LengthAwarePaginator
    {
        return ProductCollection::query()
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('name', 'like', '%'.$search.'%')->orWhere('slug', 'like', '%'.$search.'%')))
            ->withCount(['products', 'sections'])
            ->orderByDesc('updated_at')->orderByDesc('product_collection_id')
            ->paginate(15)->withQueryString();
    }

    public function members(ProductCollection $collection): Collection
    {
        return $collection->products()->with('vendor')
            ->orderByPivot('sort_order')->orderBy('products.product_id')
            ->limit(config('promotions.max_collection_products'))->get();
    }

    public function recentChoices(): Collection
    {
        return ProductCollection::query()->orderByDesc('product_collection_id')->limit(50)->get();
    }

    public function findOptional(int $id): ?ProductCollection
    {
        return ProductCollection::query()->find($id);
    }

    public function findForPreview(int $id): ProductCollection
    {
        return ProductCollection::query()->findOrFail($id);
    }

    public function publishedBySlug(string $slug): ProductCollection
    {
        return ProductCollection::query()->published()->where('slug', $slug)->firstOrFail();
    }
}
