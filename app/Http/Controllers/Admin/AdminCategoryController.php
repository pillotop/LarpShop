<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Queries\Categories\CategoryQuery;
use App\Services\Categories\CategoryManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminCategoryController extends Controller
{
    public function __construct(
        private CategoryQuery $categories,
        private CategoryManagementService $categoryService
    ) {}

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
        $validated = $this->validateInput($request);

        $category = $this->categoryService->create($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', "Category '{$category->name}' has been created.");
    }

    /**
     * UC-41: Update a category, preventing circular hierarchies.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $this->validateInput($request);

        $this->categoryService->update($category, $validated);

        return redirect()->route('admin.categories.index')
            ->with('success', "Category '{$category->name}' has been updated.");
    }

    /**
     * UC-41: Delete a category only when it has no subcategories or products.
     */
    public function destroy(Category $category): RedirectResponse
    {
        $warning = $this->categoryService->delete($category);

        if ($warning !== null) {
            return back()->with('warning', $warning);
        }

        return redirect()->route('admin.categories.index')
            ->with('success', "Category '{$category->name}' has been deleted.");
    }

    /**
     * Validate incoming request payload before passing to the service.
     *
     * @return array{name: string, slug?: string|null, parent_id?: int|null, description?: string|null}
     */
    private function validateInput(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100'],
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'category_id')],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
