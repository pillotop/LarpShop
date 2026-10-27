<?php

namespace App\Services\Vendors;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Vendor;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class VendorProductVariantService
{
    /**
     * Create a new variant for a product owned by the given vendor.
     *
     * @param  array{name: string, sku?: ?string, price: float|numeric-string, stock: int, is_active?: bool}  $data
     *
     * @throws AuthorizationException
     */
    public function createVariant(Vendor $vendor, Product $product, array $data): ProductVariant
    {
        return DB::transaction(function () use ($vendor, $product, $data) {
            $lockedProduct = Product::where('product_id', $product->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedProduct->vendor_id !== (int) $vendor->vendor_id) {
                throw new AuthorizationException('You are not authorized to add variants to this product.');
            }

            return ProductVariant::create([
                'product_id' => $lockedProduct->product_id,
                'name' => $data['name'],
                'sku' => $data['sku'] ?? null,
                'price' => $data['price'],
                'stock' => $data['stock'],
                'is_active' => $data['is_active'] ?? true,
            ]);
        }, attempts: 3);
    }

    /**
     * Update an existing product variant.
     *
     * @param  array{name?: string, sku?: ?string, price?: float|numeric-string, stock?: int, is_active?: bool}  $data
     *
     * @throws AuthorizationException
     */
    public function updateVariant(Vendor $vendor, Product $product, ProductVariant $variant, array $data): ProductVariant
    {
        return DB::transaction(function () use ($vendor, $product, $variant, $data) {
            $lockedProduct = Product::where('product_id', $product->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedProduct->vendor_id !== (int) $vendor->vendor_id) {
                throw new AuthorizationException('You are not authorized to update variants for this product.');
            }

            $lockedVariant = ProductVariant::where('variant_id', $variant->variant_id)
                ->where('product_id', $lockedProduct->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedVariant->update(array_filter([
                'name' => $data['name'] ?? null,
                'sku' => array_key_exists('sku', $data) ? $data['sku'] : $lockedVariant->sku,
                'price' => $data['price'] ?? null,
                'stock' => array_key_exists('stock', $data) ? $data['stock'] : null,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : null,
            ], fn ($val) => $val !== null));

            return $lockedVariant->fresh();
        }, attempts: 3);
    }

    /**
     * Delete an existing product variant.
     *
     * @throws AuthorizationException
     */
    public function deleteVariant(Vendor $vendor, Product $product, ProductVariant $variant): bool
    {
        return DB::transaction(function () use ($vendor, $product, $variant) {
            $lockedProduct = Product::where('product_id', $product->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedProduct->vendor_id !== (int) $vendor->vendor_id) {
                throw new AuthorizationException('You are not authorized to delete variants from this product.');
            }

            $lockedVariant = ProductVariant::where('variant_id', $variant->variant_id)
                ->where('product_id', $lockedProduct->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            return (bool) $lockedVariant->delete();
        }, attempts: 3);
    }
}
