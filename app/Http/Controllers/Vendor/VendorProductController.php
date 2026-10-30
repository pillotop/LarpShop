<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\StoreVendorProductRequest;
use App\Http\Requests\Vendor\UpdateProductStockRequest;
use App\Http\Requests\Vendor\UpdateVendorProductRequest;
use App\Models\Product;
use App\Models\Vendor;
use App\Queries\Categories\CategoryQuery;
use App\Queries\Vendors\VendorProductQuery;
use App\Queries\Vendors\VendorQuery;
use App\Services\Vendors\VendorProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VendorProductController extends Controller
{
    public function __construct(
        private VendorProductQuery $products,
        private VendorProductService $productService,
        private CategoryQuery $categories,
        private VendorQuery $vendors,
    ) {}

    /**
     * Display list of products and inventory controls belonging exclusively to the authenticated vendor.
     */
    public function index(Request $request): View
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'availability' => ['nullable', Rule::in(array_keys(VendorProductQuery::AVAILABILITY_FILTERS))],
            'status' => ['nullable', Rule::in(['all', ...Product::STATUSES])],
        ]);

        return view('vendor.products.index', [
            'vendor' => $vendor,
            'ownedVendors' => $request->attributes->get('owned_active_vendors') ?? $this->vendors->activeForUser($request->user()),
            'products' => $this->products->forVendor($vendor, $filters),
            'summary' => $this->products->inventorySummary($vendor),
            'categories' => $this->categories->allForSelect(),
            'availabilityFilters' => VendorProductQuery::AVAILABILITY_FILTERS,
            'filters' => [
                'q' => trim((string) ($filters['q'] ?? '')),
                'category_id' => isset($filters['category_id']) ? (int) $filters['category_id'] : null,
                'availability' => $filters['availability'] ?? 'all',
                'status' => $filters['status'] ?? 'all',
            ],
        ]);
    }

    /**
     * UC-30: Display form to create a new product listing.
     */
    public function create(Request $request): View
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();

        return view('vendor.products.create', [
            'vendor' => $vendor,
            'ownedVendors' => $request->attributes->get('owned_active_vendors') ?? $this->vendors->activeForUser($request->user()),
            'categories' => $this->categories->allForSelect(),
        ]);
    }

    /**
     * UC-30: Store a newly created product listing.
     */
    public function store(StoreVendorProductRequest $request): RedirectResponse
    {
        /** @var Vendor $vendor */
        $vendor = $request->vendor();

        $product = $this->productService->create(
            $vendor,
            $request->validated(),
            $request->file('images', []),
        );

        return redirect()->route('vendor.products.index')
            ->with('success', "Product '{$product->name}' has been created successfully.");
    }

    /**
     * UC-31: Display form to edit an existing vendor product listing.
     */
    public function edit(Request $request, Product $product): View
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();

        $this->productService->authorizeProductOwnership($vendor, $product);

        $product->load(['category', 'images' => fn ($q) => $q->orderBy('sort_order')->orderBy('product_image_id')]);

        return view('vendor.products.edit', [
            'vendor' => $vendor,
            'ownedVendors' => $request->attributes->get('owned_active_vendors') ?? $this->vendors->activeForUser($request->user()),
            'product' => $product,
            'categories' => $this->categories->allForSelect(),
        ]);
    }

    /**
     * UC-31: Update an existing vendor product listing.
     */
    public function update(UpdateVendorProductRequest $request, Product $product): RedirectResponse
    {
        /** @var Vendor $vendor */
        $vendor = $request->vendor();

        $updated = $this->productService->update($vendor, $product, $request->validated());

        return redirect()->route('vendor.products.index')
            ->with('success', "Product '{$updated->name}' has been updated successfully.");
    }

    /**
     * UC-31 & UC-33: Toggle product visibility (hide/show in the public catalog).
     */
    public function toggleActive(Request $request, Product $product): RedirectResponse
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();

        $updated = $this->productService->toggleVisibility($vendor, $product);

        $visibility = $updated->is_active ? 'visible in the catalog' : 'hidden from the catalog';

        return redirect()->route('vendor.products.index')
            ->with('success', "Product '{$updated->name}' is now {$visibility}.");
    }

    /**
     * UC-33: Update inventory stock and availability for a vendor product.
     */
    public function updateStock(UpdateProductStockRequest $request, Product $product): RedirectResponse
    {
        /** @var Vendor $vendor */
        $vendor = $request->vendor();

        $validated = $request->validated();

        $updated = $this->productService->updateStock(
            $vendor,
            $product,
            (int) $validated['stock'],
            array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : null,
        );

        return redirect()->route('vendor.products.index')
            ->with('success', "Inventory for '{$updated->name}' has been updated to {$updated->stock} in stock.");
    }
}
