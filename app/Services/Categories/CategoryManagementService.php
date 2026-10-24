<?php

namespace App\Services\Categories;

use App\Models\Category;
use App\Queries\Categories\CategoryQuery;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CategoryManagementService
{
    public function __construct(private CategoryQuery $categories) {}

    /**
     * Create a category with normalized slug and circular hierarchy prevention.
     *
     * @param  array{name: string, slug?: string|null, parent_id?: int|null, description?: string|null}  $data
     */
    public function create(array $data): Category
    {
        $normalized = $this->normalizeData($data);

        return Category::create($normalized);
    }

    /**
     * Update a category with normalized slug and circular hierarchy prevention.
     *
     * @param  array{name: string, slug?: string|null, parent_id?: int|null, description?: string|null}  $data
     */
    public function update(Category $category, array $data): Category
    {
        $normalized = $this->normalizeData($data, $category);

        $category->update($normalized);

        return $category;
    }

    /**
     * Delete a category only when it has no subcategories or products.
     * Returns a warning message string if deletion cannot proceed, or null on success.
     */
    public function delete(Category $category): ?string
    {
        if ($category->children()->exists()) {
            return "Category '{$category->name}' cannot be deleted because it has subcategories.";
        }

        if ($category->products()->exists()) {
            return "Category '{$category->name}' cannot be deleted because it still contains products.";
        }

        $category->delete();

        return null;
    }

    /**
     * Normalize slug, ensure uniqueness, and block circular parent assignments.
     *
     * @param  array{name: string, slug?: string|null, parent_id?: int|null, description?: string|null}  $data
     * @return array{name: string, slug: string, parent_id: int|null, description: string|null}
     */
    public function normalizeData(array $data, ?Category $category = null): array
    {
        $slug = Str::slug($data['slug'] ?? '') ?: Str::slug($data['name']);

        if ($slug === '') {
            throw ValidationException::withMessages([
                'slug' => 'A valid slug could not be generated. Please provide one.',
            ]);
        }

        $slugTaken = Category::query()
            ->where('slug', $slug)
            ->when($category, fn ($q) => $q->whereKeyNot($category->category_id))
            ->exists();

        if ($slugTaken) {
            throw ValidationException::withMessages([
                'slug' => 'This slug is already used by another category.',
            ]);
        }

        $parentId = isset($data['parent_id']) && $data['parent_id'] !== '' ? (int) $data['parent_id'] : null;

        if ($category !== null && $parentId !== null) {
            $circular = $parentId === $category->category_id
                || in_array($parentId, $this->categories->descendantIds($category->category_id), true);

            if ($circular) {
                throw ValidationException::withMessages([
                    'parent_id' => 'A category cannot be moved under itself or one of its descendants.',
                ]);
            }
        }

        return [
            'name' => $data['name'],
            'slug' => $slug,
            'parent_id' => $parentId,
            'description' => $data['description'] ?? null,
        ];
    }
}
