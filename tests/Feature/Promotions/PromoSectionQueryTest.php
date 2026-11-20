<?php

namespace Tests\Feature\Promotions;

use App\Models\ProductCollection;
use App\Models\PromoSection;
use App\Queries\Promotions\PromoSectionQuery;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class PromoSectionQueryTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    private function collection(): ProductCollection
    {
        $collection = ProductCollection::factory()->published()->create();
        $collection->products()->attach($this->product());

        return $collection;
    }

    public function test_visibility_excludes_drafts_empty_collections_unknown_layouts_and_other_placements(): void
    {
        $collection = $this->collection();
        $collection->sections()->create(['is_active' => false]);
        $collection->sections()->create(['is_active' => true, 'layout' => 'untrusted-template']);
        $collection->sections()->create(['is_active' => true, 'placement' => 'vendor_header']);
        ProductCollection::factory()->create()->sections()->create(['is_active' => true]);
        ProductCollection::factory()->published()->create()->sections()->create(['is_active' => true]);
        $expected = $collection->sections()->create(['is_active' => true]);

        $data = app(PromoSectionQuery::class)->visibleForPlacement('homepage_main');
        $this->assertCount(1, $data);
        $this->assertTrue($expected->is($data->first()->section));
        $this->assertSame($collection->name, $data->first()->heading());
        $this->assertSame($collection->products->modelKeys(), $data->first()->products->modelKeys());
    }

    public function test_schedule_boundaries_are_exact_and_can_be_read_at_an_explicit_time(): void
    {
        $this->travelTo(now()->startOfDay()->addHours(12));
        $collection = $this->collection();
        $active = $collection->sections()->create([
            'is_active' => true, 'starts_at' => now(), 'expires_at' => now()->addSecond(),
        ]);
        $future = $collection->sections()->create(['is_active' => true, 'starts_at' => now()->addSecond()]);
        $collection->sections()->create(['is_active' => true, 'expires_at' => now()]);
        $collection->sections()->create(['is_active' => true, 'expires_at' => now()->subSecond()]);
        $query = app(PromoSectionQuery::class);
        $this->assertSame([$active->getKey()], $query->visibleForPlacement('homepage_main')
            ->map(fn ($data) => $data->section->getKey())->all());
        $this->assertSame([$future->getKey()], $query->visibleForPlacement('homepage_main', now()->addSecond())
            ->map(fn ($data) => $data->section->getKey())->all());
    }

    public function test_stable_section_order_and_limits_are_applied_after_excluding_empty_sections(): void
    {
        config(['promotions.max_sections' => 2]);
        ProductCollection::factory()->published()->create()->sections()->create(['is_active' => true]);
        $collection = $this->collection();
        $last = $collection->sections()->create(['is_active' => true, 'sort_order' => 9]);
        $first = $collection->sections()->create(['is_active' => true, 'sort_order' => 1, 'heading' => 'Holiday picks']);
        $second = $collection->sections()->create(['is_active' => true, 'sort_order' => 1]);

        $data = app(PromoSectionQuery::class)->visibleForPlacement('homepage_main');
        $this->assertSame([$first->getKey(), $second->getKey()],
            $data->map(fn ($item) => $item->section->getKey())->all());
        $this->assertSame('Holiday picks', $data->first()->heading());
        $this->assertModelExists($last);
    }

    public function test_sections_disappear_when_products_become_unavailable_and_return_when_restored(): void
    {
        $collection = $this->collection();
        $collection->sections()->create(['is_active' => true]);
        $product = $collection->products->first();
        $query = app(PromoSectionQuery::class);
        $this->assertCount(1, $query->visibleForPlacement('homepage_main'));
        $product->update(['stock' => 0]);
        $this->assertCount(0, $query->visibleForPlacement('homepage_main'));
        $product->update(['stock' => 1]);
        $product->vendor->update(['status' => 'suspended']);
        $this->assertCount(0, $query->visibleForPlacement('homepage_main'));
        $product->vendor->update(['status' => 'approved']);
        $this->assertCount(1, $query->visibleForPlacement('homepage_main'));
        $collection->update(['is_published' => false]);
        $this->assertCount(0, $query->visibleForPlacement('homepage_main'));
    }

    public function test_layouts_define_their_own_product_limits(): void
    {
        config(['promotions.layouts.grid.product_limit' => 2, 'promotions.layouts.row.product_limit' => 3]);
        $collection = $this->collection();
        for ($index = 0; $index < 4; $index++) {
            $collection->products()->attach($this->product('Layout item '.$index));
        }
        $collection->sections()->create(['is_active' => true, 'layout' => 'grid']);
        $collection->sections()->create(['is_active' => true, 'layout' => 'row']);

        $data = app(PromoSectionQuery::class)->visibleForPlacement('homepage_main');
        $this->assertCount(2, $data[0]->products);
        $this->assertCount(3, $data[1]->products);
    }

    public function test_unknown_placement_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(PromoSectionQuery::class)->visibleForPlacement('../../arbitrary');
    }

    public function test_factory_and_casts_use_the_custom_keys_and_relationships(): void
    {
        $section = PromoSection::factory()->active()->create([
            'starts_at' => now(), 'image_variants' => [['path' => 'promos/example.webp', 'width' => 384]],
        ]);
        $section->refresh();
        $this->assertTrue($section->is_active);
        $this->assertInstanceOf(CarbonImmutable::class, $section->starts_at);
        $this->assertSame(384, $section->image_variants[0]['width']);
        $this->assertTrue($section->collection->sections->first()->is($section));
        $this->assertSame('promo_section_id', $section->getKeyName());
        $this->assertSame('product_collection_id', $section->collection->getKeyName());
    }
}
