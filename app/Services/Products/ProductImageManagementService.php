<?php

namespace App\Services\Products;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Vendor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductImageManagementService
{
    /**
     * Upload one or multiple images for a product.
     *
     * @param  array<UploadedFile>  $uploadedFiles
     * @return array<ProductImage>
     */
    public function uploadImages(Vendor $vendor, Product $product, array $uploadedFiles, ?string $altText = null): array
    {
        $this->authorizeProductOwnership($vendor, $product);

        $savedImages = [];
        $currentMaxSort = (int) $product->images()->max('sort_order');

        foreach ($uploadedFiles as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store('products', 'public');
            $currentMaxSort++;

            $image = new ProductImage([
                'path' => $path,
                'alt_text' => $altText ?: $product->name,
                'sort_order' => $currentMaxSort,
            ]);

            $product->images()->save($image);
            $savedImages[] = $image;
        }

        return $savedImages;
    }

    /**
     * Delete a product image from database and storage disk.
     */
    public function deleteImage(Vendor $vendor, Product $product, ProductImage $image): bool
    {
        $this->authorizeProductOwnership($vendor, $product);

        if ((int) $image->product_id !== (int) $product->product_id) {
            throw ValidationException::withMessages([
                'image' => 'The selected image does not belong to this product.',
            ]);
        }

        if ($image->path && Storage::disk('public')->exists($image->path)) {
            Storage::disk('public')->delete($image->path);
        }

        return (bool) $image->delete();
    }

    /**
     * Set a specific image as the primary image (sort_order = 1).
     */
    public function setPrimaryImage(Vendor $vendor, Product $product, ProductImage $image): void
    {
        $this->authorizeProductOwnership($vendor, $product);

        if ((int) $image->product_id !== (int) $product->product_id) {
            throw ValidationException::withMessages([
                'image' => 'The selected image does not belong to this product.',
            ]);
        }

        DB::transaction(function () use ($product, $image) {
            $product->images()
                ->where('product_image_id', '!=', $image->product_image_id)
                ->increment('sort_order', 1);

            $image->sort_order = 1;
            $image->save();
        });
    }

    /**
     * Verify that the vendor owns the product or user is admin.
     */
    public function authorizeProductOwnership(Vendor $vendor, Product $product): void
    {
        if ((int) $product->vendor_id !== (int) $vendor->vendor_id && ! $vendor->user?->isAdmin()) {
            abort(403, 'You do not have permission to manage images for this product.');
        }
    }
}
