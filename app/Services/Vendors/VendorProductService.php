<?php

namespace App\Services\Vendors;

use App\Models\Product;
use App\Models\Vendor;
use App\Queries\Vendors\VendorProductQuery;
use App\Services\Products\ProductImageManagementService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VendorProductService
{
    public function __construct(
        private VendorProductQuery $products,
        private ProductImageManagementService $images,
    ) {}

    /**
     * UC-30: Create a new product listing for the authenticated vendor store.
     *
     * @param  array{name: string, slug?: string|null, category_id: int, price: float|string, stock: int, description?: string|null, is_active?: bool, alt_text?: string|null}  $data
     * @param  array<UploadedFile>  $uploadedImages
     */
    public function create(Vendor $vendor, array $data, array $uploadedImages = []): Product
    {
        return DB::transaction(function () use ($vendor, $data, $uploadedImages) {
            $slug = $this->resolveUniqueSlug($data['slug'] ?? null, $data['name']);

            $product = $vendor->products()->create([
                'category_id' => (int) $data['category_id'],
                'name' => trim($data['name']),
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'stock' => (int) $data['stock'],
                'status' => Product::STATUS_APPROVED,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
            ]);

            if (! empty($uploadedImages)) {
                $this->images->uploadImages(
                    $vendor,
                    $product,
                    $uploadedImages,
                    $data['alt_text'] ?? null,
                );
            }

            return $product;
        });
    }

    /**
     * UC-31: Update an existing vendor-owned product listing.
     *
     * @param  array{name: string, slug?: string|null, category_id: int, price: float|string, stock: int, description?: string|null, is_active?: bool}  $data
     */
    public function update(Vendor $vendor, Product $product, array $data): Product
    {
        $this->authorizeProductOwnership($vendor, $product);

        return DB::transaction(function () use ($vendor, $product, $data) {
            $locked = $this->products->findOwned($vendor, $product->product_id, lockForUpdate: true);

            $slug = $this->resolveUniqueSlug(
                $data['slug'] ?? null,
                $data['name'],
                $locked->product_id,
            );

            $locked->update([
                'category_id' => (int) $data['category_id'],
                'name' => trim($data['name']),
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'stock' => (int) $data['stock'],
                'is_active' => array_key_exists('is_active', $data)
                    ? (bool) $data['is_active']
                    : $locked->is_active,
            ]);

            return $locked;
        });
    }

    /**
     * UC-31 & UC-33: Toggle product visibility (hide/show in the public catalog).
     */
    public function toggleVisibility(Vendor $vendor, Product $product): Product
    {
        $this->authorizeProductOwnership($vendor, $product);

        return DB::transaction(function () use ($vendor, $product) {
            $locked = $this->products->findOwned($vendor, $product->product_id, lockForUpdate: true);

            $locked->is_active = ! $locked->is_active;
            $locked->save();

            return $locked;
        });
    }

    /**
     * UC-33: Update inventory stock and optional availability flag under a row lock.
     */
    public function updateStock(Vendor $vendor, Product $product, int $stock, ?bool $isActive = null): Product
    {
        $this->authorizeProductOwnership($vendor, $product);

        if ($stock < 0) {
            throw ValidationException::withMessages([
                'stock' => 'Stock quantity cannot be negative.',
            ]);
        }

        return DB::transaction(function () use ($vendor, $product, $stock, $isActive) {
            $locked = $this->products->findOwned($vendor, $product->product_id, lockForUpdate: true);

            $locked->stock = $stock;

            if ($isActive !== null) {
                $locked->is_active = $isActive;
            }

            $locked->save();

            return $locked;
        });
    }

    /**
     * Verify that the given vendor owns the product.
     */
    public function authorizeProductOwnership(Vendor $vendor, Product $product): void
    {
        if ((int) $product->vendor_id !== (int) $vendor->vendor_id) {
            abort(403, 'You do not have permission to manage this product.');
        }
    }

    /**
     * Generate a unique URL-safe slug for a product.
     */
    private function resolveUniqueSlug(?string $rawSlug, string $name, ?int $ignoreProductId = null): string
    {
        $baseSlug = Str::slug($rawSlug ?: $name);

        if ($baseSlug === '') {
            $baseSlug = 'product';
        }

        $slug = $baseSlug;
        $counter = 1;

        while (
            Product::query()
                ->where('slug', $slug)
                ->when($ignoreProductId, fn ($q) => $q->whereKeyNot($ignoreProductId))
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
