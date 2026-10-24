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
    // <<<<<<< HEAD
    /**
     * Get all categories with vendor-specific and active hierarchy.
     */
    public function getCategoriesForVendor(Vendor $vendor): Collection
    {
        return Category::with(['parent', 'children'])
            ->where(function ($query) use ($vendor) {
                $query->whereNull('vendor_id')
                    ->orWhere('vendor_id', $vendor->vendor_id);
            })
            ->orderBy('category_id')
            ->get();
    }

    /**
     * Get list of parentable categories (must be active and not the given category).
     */
    public function getEligibleParentCategories(?int $excludeCategoryId = null): Collection
    {
        $query = Category::where('is_active', true)
            ->whereNull('parent_id'); // 1-level parent hierarchy standard

        if ($excludeCategoryId) {
            $query->where('category_id', '!=', $excludeCategoryId);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * UC-30: Create a new category for a vendor.
     */
    public function createForVendor(Vendor $vendor, array $data): Category
    {
        $slug = ! empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($data['name']);

        // Ensure unique slug
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
            'vendor_id' => $vendor->vendor_id,
            'is_active' => true,
        ]);
    }

    /**
     * UC-31: Update an existing vendor-owned category.
     */
    public function updateForVendor(Vendor $vendor, Category $category, array $data): Category
    {
        $this->authorizeVendorOwnership($vendor, $category);

        $parentId = ! empty($data['parent_id']) ? (int) $data['parent_id'] : null;

        if ($parentId === (int) $category->category_id) {
            throw ValidationException::withMessages([
                'parent_id' => 'Danh mục cha không thể là chính danh mục này.',
            ]);
        }

        $slug = ! empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($data['name']);

        // Ensure unique slug (ignoring current category)
        $originalSlug = $slug;
        $counter = 1;
        while (Category::where('slug', $slug)->where('category_id', '!=', $category->category_id)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        $category->update([
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'parent_id' => $parentId,
        ]);

        return $category;
    }

    /**
     * UC-31: Toggle active / disabled status of a category.
     */
    public function toggleActiveForVendor(Vendor $vendor, Category $category): Category
    {
        $this->authorizeVendorOwnership($vendor, $category);

        $category->is_active = ! $category->is_active;
        $category->save();

        return $category;
    }

    /**
     * Authorize that the vendor owns the category or is an administrator.
     */
    private function authorizeVendorOwnership(Vendor $vendor, Category $category): void
    {
        if ((int) $category->vendor_id !== (int) $vendor->vendor_id && ! $vendor->user?->isAdmin()) {
            throw ValidationException::withMessages([
                'category' => 'Bạn không có quyền chỉnh sửa danh mục hệ thống hoặc của gian hàng khác.',
            ]);
        }
    }

    // =======

    public function __construct(private CategoryQuery $categories)
    {}

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
        // >>>>>>> f17dd00 (refactor(admin): address PR #12 review comments with services, components, and route split)
    }
}
