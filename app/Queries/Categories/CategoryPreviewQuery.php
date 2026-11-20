<?php

namespace App\Queries\Categories;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CategoryPreviewQuery
{
    /** Fetch a bounded sample in one query, rather than loading every product/image. */
    public function images(array $categoryIds, int $limit): Collection
    {
        if ($categoryIds === []) {
            return collect();
        }

        $ranked = Product::query()->approved()->active()
            ->join('product_images', 'product_images.product_id', '=', 'products.product_id')
            ->whereHas('category', fn ($query) => $query->active())
            ->whereIn('products.category_id', $categoryIds)
            ->whereNotNull('product_images.path')->where('product_images.path', '<>', '')
            ->select(['products.category_id', 'products.name', 'product_images.path', 'product_images.alt_text'])
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY products.category_id ORDER BY products.product_id DESC, product_images.sort_order, product_images.product_image_id) AS image_rank');

        return DB::query()->fromSub($ranked, 'category_images')
            ->where('image_rank', '<=', $limit)
            ->orderBy('category_id')->orderBy('image_rank')->get()->groupBy('category_id');
    }
}
