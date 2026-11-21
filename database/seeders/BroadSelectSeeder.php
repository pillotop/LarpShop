<?php

namespace Database\Seeders;

use App\Enums\PromoSource;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\PromoSection;
use App\Queries\Products\ProductCatalogQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BroadSelectSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $placement = config('promotions.broad_select.placement');

            // Initialize once; existing groups may have been renamed, reordered, or disabled.
            if (PromoSection::where('placement', $placement)->exists()) {
                return;
            }

            $products = app(ProductCatalogQuery::class)
                ->constrainCollectionVisibility(Product::query(), inStockOnly: true);

            if (! (clone $products)->exists()) {
                return;
            }

            PromoSection::create([
                'source' => PromoSource::NewArrivals->value,
                'heading' => 'New arrivals',
                'placement' => $placement,
                'layout' => 'row',
                'is_active' => true,
                'sort_order' => 0,
            ]);

            $collections = [
                ['handpicked-equipment', 'Handpicked equipment for your next adventure', ['sports-and-outdoors']],
                ['weekend-favorites', 'Weekend favorites', ['electronics', 'home-and-living']],
                ['costume-essentials', 'Costume essentials', ['arts-crafts-and-sewing']],
                ['gifts-and-accessories', 'Gifts and accessories', ['accessories', 'beauty-and-personal-care']],
                ['explore-more', 'Explore more discoveries', []],
            ];

            foreach ($collections as $position => [$slug, $name, $categorySlugs]) {
                $ids = (clone $products)
                    ->when($categorySlugs !== [], fn (Builder $query) => $query->whereHas('category',
                        fn (Builder $category) => $category->whereIn('slug', $categorySlugs)
                            ->orWhereHas('parent', fn (Builder $parent) => $parent->whereIn('slug', $categorySlugs))))
                    ->orderBy('products.product_id')
                    ->limit(config('promotions.broad_select.product_limit'))
                    ->pluck('products.product_id');

                if ($ids->isEmpty()) {
                    continue;
                }

                $collection = ProductCollection::firstOrCreate(['slug' => 'broad-select-'.$slug], [
                    'name' => $name,
                    'description' => 'A sample collection curated from our stores.',
                    'is_published' => true,
                ]);

                if (! $collection->wasRecentlyCreated) {
                    continue;
                }

                $collection->products()->sync($ids->mapWithKeys(
                    fn ($id, $index) => [$id => ['sort_order' => $index]],
                )->all());
                $collection->sections()->create([
                    'source' => PromoSource::Collection->value,
                    'heading' => $name,
                    'placement' => $placement,
                    'layout' => 'row',
                    'is_active' => true,
                    'sort_order' => $position + 1,
                ]);
            }
        });
    }
}
