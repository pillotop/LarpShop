<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Vendor;
use Database\Seeders\Support\DemoAssetManager;
use Database\Seeders\Support\DemoSeedContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the multi-vendor product catalog, local product images, variants, and inventory/moderation states.
 */
class DemoCatalogSeeder extends Seeder
{
    public function __construct(
        private DemoSeedContext $context,
        private DemoAssetManager $assets,
    ) {}

    public function run(): void
    {
        $this->assets->syncToPublicDisk();

        $blueprint = require __DIR__.'/data/catalog_definitions.php';
        $vendorsByName = Vendor::query()->get()->keyBy('store_name');
        $categoriesBySlug = Category::query()->get()->keyBy('slug');

        foreach ($blueprint['featured'] as $idx => $item) {
            $vendor = $vendorsByName->get($item['vendor']);
            $category = $categoriesBySlug->get($item['category']);
            if (! $vendor || ! $category) {
                continue;
            }

            $product = $this->upsertProduct(
                slug: $item['slug'],
                vendor: $vendor,
                category: $category,
                name: $item['name'],
                description: $item['description'],
                price: (float) $item['price'],
                initialStock: (int) $item['stock'],
                status: Product::STATUS_APPROVED,
                isActive: true,
                createdDaysAgo: 185 - ($idx * 3),
            );

            $this->syncImages($product, [
                ['file' => $item['image'], 'alt' => $item['alt']],
            ]);

            $this->syncVariants($product, $item['variants']);
        }

        $globalIndex = 0;
        $catIndex = 0;

        foreach ($blueprint['categories'] as $catSlug => $group) {
            $category = $categoriesBySlug->get($catSlug);
            $vendor = $vendorsByName->get($group['vendor']);
            if (! $category || ! $vendor) {
                $catIndex++;

                continue;
            }

            foreach ($group['items'] as $offset => $itemRow) {
                [$name, $description, $price] = $itemRow;
                $primaryImage = $itemRow[3] ?? ($offset % 2 === 1 && ! empty($group['secondary_image']) ? $group['secondary_image'] : $group['image']);

                [$status, $isActive, $stock] = $this->stateForIndex($globalIndex);
                $slug = Str::slug($name);

                // Interleave timestamps across categories so default newest sort mixes diverse product types.
                $createdDaysAgo = max(1, 175 - (($offset * 32) + $catIndex));

                $product = $this->upsertProduct(
                    slug: $slug,
                    vendor: $vendor,
                    category: $category,
                    name: $name,
                    description: $description,
                    price: (float) $price,
                    initialStock: $stock,
                    status: $status,
                    isActive: $isActive,
                    createdDaysAgo: $createdDaysAgo,
                );

                $images = [['file' => $primaryImage, 'alt' => $name.' - Primary view']];
                if ($primaryImage !== $group['image']) {
                    $images[] = ['file' => $group['image'], 'alt' => $name.' - Category view'];
                } elseif (! empty($group['secondary_image'])) {
                    $images[] = ['file' => $group['secondary_image'], 'alt' => $name.' - Detail view'];
                }
                $this->syncImages($product, $images);

                if ($globalIndex % 2 === 0 && $stock !== 0) {
                    $this->syncDefaultVariants($product, $slug, (float) $price, $stock);
                }

                $globalIndex++;
            }

            $catIndex++;
        }
    }

    /**
     * @return array{0: string, 1: bool, 2: int}
     */
    private function stateForIndex(int $idx): array
    {
        if (in_array($idx, [20, 45, 70, 90, 115, 138], true)) {
            return [Product::STATUS_PENDING, true, 25];
        }
        if (in_array($idx, [38, 76, 104, 134], true)) {
            return [Product::STATUS_REJECTED, true, 15];
        }
        if (in_array($idx, [26, 62, 100, 140], true)) {
            return [Product::STATUS_APPROVED, false, 18];
        }
        if (in_array($idx, [16, 32, 48, 64, 80, 96, 112, 128, 144], true)) {
            return [Product::STATUS_APPROVED, true, 0];
        }
        if (in_array($idx, [11, 23, 35, 47, 59, 71, 83, 95, 107, 119, 131, 143], true)) {
            return [Product::STATUS_APPROVED, true, ($idx % 5) + 1];
        }

        return [Product::STATUS_APPROVED, true, $this->context->deterministicInt('stock:'.$idx, 45, 95)];
    }

    private function upsertProduct(
        string $slug,
        Vendor $vendor,
        Category $category,
        string $name,
        string $description,
        float $price,
        int $initialStock,
        string $status,
        bool $isActive,
        int $createdDaysAgo,
    ): Product {
        $timestamp = $this->context->anchor()->subDays($createdDaysAgo);
        $existing = Product::query()->where('slug', $slug)->first();

        if ($existing) {
            $existing->forceFill([
                'vendor_id' => $vendor->vendor_id,
                'category_id' => $category->category_id,
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'status' => $status,
                'is_active' => $isActive,
                'created_at' => $timestamp,
            ])->save();

            return $existing;
        }

        $product = new Product([
            'vendor_id' => $vendor->vendor_id,
            'category_id' => $category->category_id,
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'price' => $price,
            'stock' => $initialStock,
            'status' => $status,
            'is_active' => $isActive,
        ]);
        $product->created_at = $timestamp;
        $product->updated_at = $timestamp;
        $product->save();

        return $product;
    }

    /**
     * @param  array<int, array{file: string, alt: string}>  $images
     */
    private function syncImages(Product $product, array $images): void
    {
        foreach ($images as $order => $img) {
            ProductImage::updateOrCreate(
                [
                    'product_id' => $product->product_id,
                    'sort_order' => $order,
                ],
                [
                    'path' => $this->assets->publicPath($img['file']),
                    'alt_text' => $img['alt'],
                ]
            );
        }
    }

    /**
     * @param  array<int, array{sku: string, name: string, price: float, stock: int}>  $variants
     */
    private function syncVariants(Product $product, array $variants): void
    {
        foreach ($variants as $variantData) {
            $existing = ProductVariant::query()->where('sku', $variantData['sku'])->first();
            if ($existing) {
                $existing->forceFill([
                    'product_id' => $product->product_id,
                    'name' => $variantData['name'],
                    'price' => $variantData['price'],
                    'is_active' => true,
                ])->save();

                continue;
            }

            ProductVariant::create([
                'product_id' => $product->product_id,
                'name' => $variantData['name'],
                'sku' => $variantData['sku'],
                'price' => $variantData['price'],
                'stock' => $variantData['stock'],
                'is_active' => true,
            ]);
        }
    }

    private function syncDefaultVariants(Product $product, string $slug, float $basePrice, int $totalStock): void
    {
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $slug), 0, 8));
        $hash = strtoupper(substr(md5($slug), 0, 4));
        $firstHalf = (int) floor($totalStock / 2);
        $secondHalf = max(0, $totalStock - $firstHalf);

        $this->syncVariants($product, [
            [
                'sku' => sprintf('%s-%s-STD', $prefix, $hash),
                'name' => 'Standard Edition',
                'price' => $basePrice,
                'stock' => $firstHalf,
            ],
            [
                'sku' => sprintf('%s-%s-DLX', $prefix, $hash),
                'name' => 'Signature Bundle',
                'price' => round($basePrice * 1.15, 2),
                'stock' => $secondHalf,
            ],
        ]);
    }
}
