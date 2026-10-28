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
     * @param  array{name: string, sku?: ?string, price: float|numeric-string, stock: int, is_active?: bool|int|string}  $data
     *
     * @throws AuthorizationException
     */
    public function createVariant(Vendor $vendor, Product $product, array $data): ProductVariant
    {
        return DB::transaction(function () use ($vendor, $product, $data) {
            $lockedProduct = Product::query()
                ->whereKey($product->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedProduct->vendor_id !== (int) $vendor->vendor_id) {
                throw new AuthorizationException('You are not authorized to add variants to this product.');
            }

            $sku = isset($data['sku']) ? trim((string) $data['sku']) : null;

            return ProductVariant::create([
                'product_id' => $lockedProduct->product_id,
                'name' => trim($data['name']),
                'sku' => $sku !== '' ? $sku : null,
                'price' => $data['price'],
                'stock' => (int) $data['stock'],
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
            ]);
        }, attempts: 3);
    }

    /**
     * Update an existing product variant.
     *
     * @param  array{name?: string, sku?: ?string, price?: float|numeric-string, stock?: int, is_active?: bool|int|string}  $data
     *
     * @throws AuthorizationException
     */
    public function updateVariant(Vendor $vendor, Product $product, ProductVariant $variant, array $data): ProductVariant
    {
        return DB::transaction(function () use ($vendor, $product, $variant, $data) {
            $lockedProduct = Product::query()
                ->whereKey($product->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedProduct->vendor_id !== (int) $vendor->vendor_id) {
                throw new AuthorizationException('You are not authorized to update variants for this product.');
            }

            $lockedVariant = ProductVariant::query()
                ->whereKey($variant->getKey())
                ->where('product_id', $lockedProduct->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            $updates = [];

            if (array_key_exists('name', $data) && $data['name'] !== null) {
                $updates['name'] = trim((string) $data['name']);
            }

            if (array_key_exists('sku', $data)) {
                $sku = $data['sku'] !== null ? trim((string) $data['sku']) : '';
                $updates['sku'] = $sku !== '' ? $sku : null;
            }

            if (array_key_exists('price', $data) && $data['price'] !== null) {
                $updates['price'] = $data['price'];
            }

            if (array_key_exists('stock', $data) && $data['stock'] !== null) {
                $updates['stock'] = (int) $data['stock'];
            }

            if (array_key_exists('is_active', $data) && $data['is_active'] !== null) {
                $updates['is_active'] = (bool) $data['is_active'];
            }

            if ($updates !== []) {
                $lockedVariant->update($updates);
            }

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
            $lockedProduct = Product::query()
                ->whereKey($product->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedProduct->vendor_id !== (int) $vendor->vendor_id) {
                throw new AuthorizationException('You are not authorized to delete variants from this product.');
            }

            $lockedVariant = ProductVariant::query()
                ->whereKey($variant->getKey())
                ->where('product_id', $lockedProduct->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            return (bool) $lockedVariant->delete();
        }, attempts: 3);
    }
}
