<?php

namespace App\Services\Categories;

use App\Models\Category;
use App\Models\Vendor;
use App\Queries\Categories\CategoryQuery;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CategoryManagementService
{
    public function __construct(private CategoryQuery $categories) {}

    /**
     * Get all platform categories with parent/child hierarchy for the vendor console.
     */
    public function getCategoriesForVendor(?Vendor $vendor = null): Collection
    {
        return Category::with(['parent', 'children'])
            ->withCount('products')
            ->orderBy('parent_id')
            ->orderBy('category_id')
            ->get();
    }

    /**
     * Get list of eligible parent categories (active root categories).
     */
    public function getEligibleParentCategories(?int $excludeCategoryId = null): Collection
    {
        $query = Category::where('is_active', true)
            ->whereNull('parent_id');

        if ($excludeCategoryId) {
            $query->where('category_id', '!=', $excludeCategoryId);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Allow a vendor to propose/create a new category without owning global categories.
     */
    public function createForVendor(Vendor $vendor, array $data): Category
    {
        $slug = ! empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($data['name']);

        $originalSlug = $slug;
        $counter = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        return Category::create([
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'parent_id' => ! empty($data['parent_id']) ? (int) $data['parent_id'] : null,
            'is_active' => true,
        ]);
    }

    /**
     * Update an existing shared category (restricted to administrators).
     */
    public function updateForVendor(Vendor $vendor, Category $category, array $data): Category
    {
        $this->authorizeSharedCategoryMutation($vendor);

        return $this->update($category, $data);
    }

    /**
     * Toggle active/disabled status of a shared category (restricted to administrators).
     */
    public function toggleActiveForVendor(Vendor $vendor, Category $category): Category
    {
        $this->authorizeSharedCategoryMutation($vendor);

        $category->is_active = ! $category->is_active;
        $category->save();

        return $category;
    }

    /**
     * Shared platform categories cannot be edited or disabled by non-admin vendors.
     */
    private function authorizeSharedCategoryMutation(Vendor $vendor): void
    {
        if (! $vendor->user?->isAdmin()) {
            throw ValidationException::withMessages([
                'category' => 'Only administrators can edit or disable shared platform categories.',
            ]);
        }
    }

    /**
     * Create a category with normalized slug and circular hierarchy prevention (UC-41).
     *
     * @param  array{name: string, slug?: string|null, parent_id?: int|null, description?: string|null}  $data
     */
    public function create(array $data): Category
    {
        $normalized = $this->normalizeData($data);

        return Category::create($normalized);
    }

    /**
     * Update a category with normalized slug and circular hierarchy prevention (UC-41).
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
     * Delete a category only when it has no subcategories or products (UC-41).
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
