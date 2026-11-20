<?php

namespace App\View\Components;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class CategoryDropdown extends Component
{
    /** @var Collection<int, array{category: Category, children: Collection}> */
    public Collection $groups;

    public Collection $categories;

    public string $contentId;

    public ?Category $selectedCategory;

    /** @var Collection<int, Category> */
    public Collection $fallbackCategories;

    /** @param iterable<Category> $categories Already loaded navigation categories. */
    public function __construct(iterable $categories)
    {
        $this->contentId = 'category-list-'.Str::uuid();
        $categories = collect($categories);
        $this->categories = $categories;
        $scope = request()->query('category', '');
        $this->selectedCategory = is_string($scope) && trim($scope) !== ''
            ? $categories->first(fn (Category $category) => $category->slug === trim($scope)
                || (string) $category->getKey() === trim($scope))
            : null;
        $children = $categories->whereNotNull('parent_id')->groupBy('parent_id');

        $this->groups = $categories->whereNull('parent_id')->values()
            ->map(fn (Category $category) => [
                'category' => $category,
                'children' => $children->get($category->getKey(), collect())->values(),
            ]);
        $this->fallbackCategories = $this->groups->isEmpty() ? $categories->values() : collect();
    }

    public function render(): View
    {
        return view('components.category-dropdown');
    }
}
