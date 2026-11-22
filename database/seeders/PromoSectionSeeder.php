<?php

namespace Database\Seeders;

use App\Models\BannerGroup;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Queries\Products\ProductCatalogQuery;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PromoSectionSeeder extends Seeder
{
    public function run(): void
    {
        $productIds = app(ProductCatalogQuery::class)
            ->constrainCollectionVisibility(Product::query(), inStockOnly: true)
            ->orderBy('products.product_id')->limit(12)->pluck('products.product_id');

        if ($productIds->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($productIds) {
            // Only initialize new demo content; re-seeding must preserve admin edits.
            $collection = ProductCollection::firstOrCreate(['slug' => 'featured-finds'], [
                'name' => 'Featured finds',
                'description' => 'A curated selection from our stores.',
                'is_published' => true,
            ]);

            if (! $collection->wasRecentlyCreated) {
                return;
            }

            $collection->products()->sync($productIds->mapWithKeys(
                fn ($id, $position) => [$id => ['sort_order' => $position]]
            )->all());
            $section = $collection->sections()->create([
                'heading' => 'Featured finds', 'layout' => 'grid',
                'placement' => 'homepage_main', 'is_active' => true,
            ]);
            $section->bannerGroup()->associate(BannerGroup::where('slug', 'demo-care-duo')->first())->save();
        });
    }
}
