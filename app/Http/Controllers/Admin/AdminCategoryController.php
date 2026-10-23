<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Queries\Categories\CategoryQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminCategoryController extends Controller
{
    public function __construct(private CategoryQuery $categories) {}

    /**
     * UC-41: List categories with search and root/sub filtering.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'level' => ['nullable', Rule::in(['all', 'root', 'sub'])],
        ]);

        $keyword = trim((string) ($validated['q'] ?? ''));
        $level = $validated['level'] ?? 'all';

        return view('admin.categories.index', [
            'categories' => $this->categories->forAdmin($keyword, $level),
            'parentOptions' => $this->categories->allForSelect(),
            'keyword' => $keyword,
            'level' => $level,
        ]);
    }

    /**
     * UC-41: Create a category.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        Category::create($data);

        return redirect()->route('admin.categories.index')
            ->with('success', "Category '{$data['name']}' has been created.");
    }

    /**
     * UC-41: Update a category, preventing circular hierarchies.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validatedData($request, $category);

        $category->update($data);

        return redirect()->route('admin.categories.index')
            ->with('success', "Category '{$category->name}' has been updated.");
    }

    /**
     * UC-41: Delete a category only when it has no subcategories or products.
     */
    public function destroy(Category $category): RedirectResponse
    {
        if ($category->children()->exists()) {
            return back()->with('warning', "Category '{$category->name}' cannot be deleted because it has subcategories.");
        }

        if ($category->products()->exists()) {
            return back()->with('warning', "Category '{$category->name}' cannot be deleted because it still contains products.");
        }

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', "Category '{$category->name}' has been deleted.");
    }

    /**
     * Validate input, normalize the slug, and guard against circular hierarchies.
     *
     * @return array{name: string, slug: string, parent_id: int|null, description: string|null}
     */
    private function validatedData(Request $request, ?Category $category = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100'],
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'category_id')],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $slug = Str::slug($validated['slug'] ?? '') ?: Str::slug($validated['name']);

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

        $parentId = isset($validated['parent_id']) ? (int) $validated['parent_id'] : null;

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
            'name' => $validated['name'],
            'slug' => $slug,
            'parent_id' => $parentId,
            'description' => $validated['description'] ?? null,
        ];
    }
}
