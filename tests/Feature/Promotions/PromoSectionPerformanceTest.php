<?php

namespace Tests\Feature\Promotions;

use App\Models\ProductCollection;
use App\Queries\Promotions\PromoSectionQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class PromoSectionPerformanceTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_eight_collections_use_a_fixed_number_of_queries_and_render_without_lazy_queries(): void
    {
        for ($index = 0; $index < 8; $index++) {
            $collection = ProductCollection::factory()->published()->create();
            $product = $this->product('Prepared product '.$index);
            $product->images()->create(['path' => 'example.webp', 'alt_text' => 'Equipment']);
            $collection->products()->attach($product);
            $collection->sections()->create(['is_active' => true, 'sort_order' => $index]);
        }
        // Resolve shared currency/view services before measuring product preparation.
        Blade::render('<span>{{ $money->format(1) }}</span>');
        DB::enableQueryLog();
        DB::flushQueryLog();
        try {
            $prepared = app(PromoSectionQuery::class)->visibleForPlacement('homepage_main');
            $this->assertCount(8, $prepared);
            $this->assertLessThanOrEqual(6, count(DB::getQueryLog()));
            DB::flushQueryLog();
            $html = Blade::render('@foreach ($prepared as $data)<x-promo-section :data="$data" />@endforeach', compact('prepared'));
            $this->assertStringContainsString('Prepared product 7', $html);
            $this->assertCount(0, DB::getQueryLog(), 'Rendering prepared promo data must not query product relations.');
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }

    public function test_batch_limit_applies_per_collection_and_each_layout_keeps_its_own_ordered_slice(): void
    {
        config(['promotions.layouts.grid.product_limit' => 2, 'promotions.layouts.row.product_limit' => 3,
            'promotions.layouts.split.product_limit' => 1]);
        foreach (['First', 'Second'] as $name) {
            $collection = ProductCollection::factory()->published()->create(['name' => $name]);
            $ids = [];
            for ($index = 0; $index < 5; $index++) {
                $product = $this->product($name.' '.$index);
                $ids[] = $product->getKey();
                $collection->products()->attach($product, ['sort_order' => 4 - $index]);
            }
            foreach (['grid' => 2, 'row' => 3, 'split' => 1] as $layout => $limit) {
                $collection->sections()->create(['is_active' => true, 'layout' => $layout]);
            }
            $expected[$collection->getKey()] = array_reverse($ids);
        }
        $prepared = app(PromoSectionQuery::class)->visibleForPlacement('homepage_main');
        $this->assertCount(6, $prepared);
        foreach ($prepared as $data) {
            $limit = config('promotions.layouts')[$data->section->layout]['product_limit'];
            $this->assertSame(array_slice($expected[$data->section->product_collection_id], 0, $limit), $data->products->modelKeys());
            $this->assertLessThanOrEqual(3, $data->section->collection->products->count());
        }
    }

    public function test_empty_homepage_uses_only_the_section_lookup_query(): void
    {
        DB::enableQueryLog();
        DB::flushQueryLog();
        try {
            $this->assertCount(0, app(PromoSectionQuery::class)->visibleForPlacement('homepage_main'));
            $this->assertCount(1, DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }
}
