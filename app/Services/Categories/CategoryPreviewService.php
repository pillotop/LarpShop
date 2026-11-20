<?php

namespace App\Services\Categories;

use App\Models\Category;
use App\Models\ProductImage;
use App\Queries\Categories\CategoryPreviewQuery;
use Illuminate\Support\Collection;

class CategoryPreviewService
{
    private const LIMIT = 3;

    public function __construct(private CategoryPreviewQuery $query) {}

    /** Curated images override product samples; empty categories inherit from descendants. */
    public function forCategories(Collection $categories): array
    {
        $categories = $categories->filter(fn (Category $category) => $category->is_active !== false);
        $samples = $this->query->images(
            $categories->filter(fn (Category $category) => $category->exists)->map(fn (Category $category) => $category->getKey())->values()->all(),
            self::LIMIT,
        );
        $own = [];
        foreach ($categories as $category) {
            $curated = collect((config('category-previews.images', [])[$category->slug] ?? []))
                ->filter(fn ($path) => is_string($path) && trim($path) !== '')
                ->take(self::LIMIT)
                ->map(fn ($path) => $this->image($path, $category->name));
            $own[$category->getKey()] = $curated->isNotEmpty() ? $curated :
                $samples->get($category->getKey(), collect())->map(
                    fn ($image) => $this->image($image->path, $image->alt_text ?: $image->name),
                );
        }

        $children = $categories->whereNotNull('parent_id')->groupBy('parent_id');
        $result = [];
        foreach ($categories as $category) {
            $result[$category->getKey()] = $this->resolve($category->getKey(), $own, $children, []);
        }

        return $result;
    }

    private function resolve(int $id, array $own, Collection $children, array $visited): array
    {
        if (isset($visited[$id])) {
            return [];
        }
        $visited[$id] = true;
        if ($own[$id]->isNotEmpty()) {
            return $own[$id]->unique('url')->values()->all();
        }
        $images = collect();
        foreach ($children->get($id, []) as $child) {
            $images = $images->concat($this->resolve($child->getKey(), $own, $children, $visited))->unique('url');
            if ($images->count() >= self::LIMIT) {
                break;
            }
        }

        return $images->take(self::LIMIT)->values()->all();
    }

    private function image(string $path, string $alt): array
    {
        $image = new ProductImage(['path' => $path]);

        return ['url' => $image->url, 'alt' => $alt];
    }
}
