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
     * List all categories for the vendor with status and ownership flags.
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
     * UC-30: Display create category form.
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
     * UC-30: Store a newly created category for the vendor.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();

        $category = $this->categoryService->createForVendor($vendor, $request->validated());

        return redirect()->route('vendor.categories.index')
            ->with('success', "Danh mục '{$category->name}' đã được tạo thành công.");
    }

    /**
     * UC-31: Display edit category form.
     */
    public function edit(Request $request, Category $category): View
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();

        if (! $category->isOwnedBy($vendor) && ! $request->user()->isAdmin()) {
            abort(403, 'Bạn không có quyền chỉnh sửa danh mục này.');
        }

        $parents = $this->categoryService->getEligibleParentCategories($category->category_id);

        return view('vendor.categories.edit', [
            'vendor' => $vendor,
            'category' => $category,
            'parentCategories' => $parents,
        ]);
    }

    /**
     * UC-31: Update an existing category.
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();

        $this->categoryService->updateForVendor($vendor, $category, $request->validated());

        return redirect()->route('vendor.categories.index')
            ->with('success', "Danh mục '{$category->name}' đã được cập nhật thành công.");
    }

    /**
     * UC-31: Toggle active / disabled status of a category.
     */
    public function toggleStatus(Request $request, Category $category): RedirectResponse
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();

        if (! $category->isOwnedBy($vendor) && ! $request->user()->isAdmin()) {
            abort(403, 'Bạn không có quyền vô hiệu hóa hoặc kích hoạt danh mục này.');
        }

        $this->categoryService->toggleActiveForVendor($vendor, $category);

        $statusText = $category->is_active ? 'kích hoạt' : 'vô hiệu hóa';

        return redirect()->route('vendor.categories.index')
            ->with('success', "Danh mục '{$category->name}' đã được {$statusText} thành công.");
    }
}
