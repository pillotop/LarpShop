<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\StoreCategoryRequest;
use App\Http\Requests\Vendor\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\Vendor;
use App\Services\Categories\CategoryManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorCategoryController extends Controller
{
    public function __construct(
        private CategoryManagementService $categoryService,
    ) {}

    /**
     * List all platform categories for the vendor with status and hierarchy.
     */
    public function index(Request $request): View
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();

        $categories = $this->categoryService->getCategoriesForVendor($vendor);

        return view('vendor.categories.index', [
            'vendor' => $vendor,
            'categories' => $categories,
        ]);
    }

    /**
     * Display create category form.
     */
    public function create(Request $request): View
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();

        $parents = $this->categoryService->getEligibleParentCategories();

        return view('vendor.categories.create', [
            'vendor' => $vendor,
            'parentCategories' => $parents,
        ]);
    }

    /**
     * Store a newly created category proposed by a vendor.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();

        $category = $this->categoryService->createForVendor($vendor, $request->validated());

        return redirect()->route('vendor.categories.index')
            ->with('success', "Category '{$category->name}' has been created successfully.");
    }

    /**
     * Display edit category form (restricted to administrators for shared platform categories).
     */
    public function edit(Request $request, Category $category): View
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();

        if (! $request->user()->isAdmin()) {
            abort(403, 'Only administrators can edit shared platform categories.');
        }

        $parents = $this->categoryService->getEligibleParentCategories($category->category_id);

        return view('vendor.categories.edit', [
            'vendor' => $vendor,
            'category' => $category,
            'parentCategories' => $parents,
        ]);
    }

    /**
     * Update an existing shared category (restricted to administrators).
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();

        $this->categoryService->updateForVendor($vendor, $category, $request->validated());

        return redirect()->route('vendor.categories.index')
            ->with('success', "Category '{$category->name}' has been updated successfully.");
    }

    /**
     * Toggle active/disabled status of a shared category (restricted to administrators).
     */
    public function toggleStatus(Request $request, Category $category): RedirectResponse
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();

        if (! $request->user()->isAdmin()) {
            abort(403, 'Only administrators can enable or disable shared platform categories.');
        }

        $this->categoryService->toggleActiveForVendor($vendor, $category);

        $statusText = $category->is_active ? 'activated' : 'disabled';

        return redirect()->route('vendor.categories.index')
            ->with('success', "Category '{$category->name}' has been {$statusText} successfully.");
    }
}
