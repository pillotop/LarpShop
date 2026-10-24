<?php

namespace App\Queries\Categories;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CategoryQuery
{
    public function forNavigation(): Collection
    {
        return Category::query()->orderBy('category_id')->get();
    }

    /**
     * UC-41: Paginated category list for the admin console with counters and optional filters.
     *
     * @param  string|null  $keyword  Matches name or slug.
     * @param  string  $level  One of 'all', 'root', 'sub'.
     */
    public function forAdmin(?string $keyword = null, string $level = 'all', int $perPage = 15): LengthAwarePaginator
    {
        $query = Category::query()
            ->with('parent')
            ->withCount(['children', 'products'])
            ->orderBy('parent_id')
            ->orderBy('name');

        if ($keyword !== null && $keyword !== '') {
            $escaped = addcslashes($keyword, '\\%_');
            $query->where(function ($q) use ($escaped) {
                $q->where('name', 'like', "%{$escaped}%")
                    ->orWhere('slug', 'like', "%{$escaped}%");
            });
        }

        if ($level === 'root') {
            $query->whereNull('parent_id');
        } elseif ($level === 'sub') {
            $query->whereNotNull('parent_id');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * UC-41: All categories for parent <select> options, ordered by name.
     */
    public function allForSelect(): Collection
    {
        return Category::query()->orderBy('name')->get(['category_id', 'parent_id', 'name']);
    }

    /**
     * UC-41: IDs of every descendant (children, grandchildren, ...) of the given category.
     * Used to block circular parent/child assignments.
     *
     * @return array<int, int>
     */
    public function descendantIds(int $categoryId): array
    {
        $childrenByParent = Category::query()
            ->whereNotNull('parent_id')
            ->get(['category_id', 'parent_id'])
            ->groupBy('parent_id');

        $ids = [];
        $stack = [$categoryId];

        while ($stack) {
            $current = array_pop($stack);
            foreach ($childrenByParent->get($current, []) as $child) {
                if (! in_array($child->category_id, $ids, true)) {
                    $ids[] = $child->category_id;
                    $stack[] = $child->category_id;
                }
            }
        }

        return $ids;
    }
}
