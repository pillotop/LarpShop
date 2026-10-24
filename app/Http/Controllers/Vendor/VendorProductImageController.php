<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\UploadProductImageRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Vendor;
use App\Services\Products\ProductImageManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorProductImageController extends Controller
{
    public function __construct(
        private ProductImageManagementService $imageService,
    ) {}

    /**
     * UC-32: View all images for a product.
     */
    public function index(Request $request, Product $product): View
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();

        $this->imageService->authorizeProductOwnership($vendor, $product);

        $product->load(['images' => fn ($q) => $q->orderBy('sort_order')->orderBy('product_image_id')]);

        return view('vendor.products.images', [
            'vendor' => $vendor,
            'product' => $product,
            'images' => $product->images,
        ]);
    }

    /**
     * UC-32: Upload one or more images for the product.
     */
    public function store(UploadProductImageRequest $request, Product $product): RedirectResponse
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();

        $uploadedFiles = $request->file('images', []);
        $altText = $request->input('alt_text');

        $this->imageService->uploadImages($vendor, $product, $uploadedFiles, $altText);

        return redirect()->route('vendor.products.images.index', $product->product_id)
            ->with('success', 'Đã tải lên '.count($uploadedFiles).' hình ảnh thành công.');
    }

    /**
     * UC-32: Delete a product image.
     */
    public function destroy(Request $request, Product $product, ProductImage $image): RedirectResponse
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();

        $this->imageService->deleteImage($vendor, $product, $image);

        return redirect()->route('vendor.products.images.index', $product->product_id)
            ->with('success', 'Đã xóa hình ảnh sản phẩm thành công.');
    }

    /**
     * UC-32: Set an image as the primary cover / thumbnail image.
     */
    public function setPrimary(Request $request, Product $product, ProductImage $image): RedirectResponse
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();

        $this->imageService->setPrimaryImage($vendor, $product, $image);

        return redirect()->route('vendor.products.images.index', $product->product_id)
            ->with('success', 'Đã đặt hình ảnh làm ảnh đại diện chính của sản phẩm.');
    }
}
