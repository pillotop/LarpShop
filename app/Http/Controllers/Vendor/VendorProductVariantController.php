<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\StoreProductVariantRequest;
use App\Http\Requests\Vendor\UpdateProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Vendor;
use App\Services\Vendors\VendorProductVariantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorProductVariantController extends Controller
{
    public function __construct(
        private VendorProductVariantService $variants,
    ) {}

    public function index(Request $request, Product $product): View
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();

        if ((int) $product->vendor_id !== (int) $vendor->vendor_id) {
            abort(403, 'Unauthorized access to this product.');
        }

        $product->load(['variants' => fn ($q) => $q->orderBy('variant_id')]);

        return view('vendor.products.variants.index', [
            'vendor' => $vendor,
            'product' => $product,
            'variants' => $product->variants,
        ]);
    }

    public function store(StoreProductVariantRequest $request, Product $product): RedirectResponse
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();

        $this->variants->createVariant($vendor, $product, $request->validated());

        return redirect()->route('vendor.products.variants.index', $product)
            ->with('success', 'Product variant added successfully.');
    }

    public function update(
        UpdateProductVariantRequest $request,
        Product $product,
        ProductVariant $variant,
    ): RedirectResponse {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();

        $this->variants->updateVariant($vendor, $product, $variant, $request->validated());

        return redirect()->route('vendor.products.variants.index', $product)
            ->with('success', 'Product variant updated successfully.');
    }

    public function destroy(
        Request $request,
        Product $product,
        ProductVariant $variant,
    ): RedirectResponse {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();

        $this->variants->deleteVariant($vendor, $product, $variant);

        return redirect()->route('vendor.products.variants.index', $product)
            ->with('success', 'Product variant removed successfully.');
    }
}
