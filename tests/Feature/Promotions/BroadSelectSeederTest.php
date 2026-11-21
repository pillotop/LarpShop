<?php

namespace Tests\Feature\Promotions;

use App\Models\Category;
use App\Models\ProductCollection;
use App\Models\PromoSection;
use App\Services\Promotions\BroadSelectService;
use Database\Seeders\BroadSelectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class BroadSelectSeederTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    public function test_empty_catalog_creates_no_demo_groups(): void
    {
        $this->seed(BroadSelectSeeder::class);
        $this->assertDatabaseCount('promo_sections', 0);
        $this->assertDatabaseCount('product_collections', 0);
    }

    public function test_seeded_groups_use_category_membership_and_preserve_admin_edits_on_rerun(): void
    {
        $members = [];
        foreach (['sports-and-outdoors', 'electronics', 'arts-crafts-and-sewing', 'accessories'] as $slug) {
            $parent = Category::create(['name' => $slug, 'slug' => $slug]);
            $product = $this->product($slug.' product');
            $product->category->update(['parent_id' => $parent->getKey()]);
            $members[$slug] = $product;
        }
        $unavailable = $this->product('Unavailable', stock: 0);
        $unavailable->update(['category_id' => $members['sports-and-outdoors']->category_id]);
        $this->seed(BroadSelectSeeder::class);
        $this->assertDatabaseCount('promo_sections', 6);
        $this->assertDatabaseCount('product_collections', 5);
        $groups = app(BroadSelectService::class)->forHomepage();
        $this->assertCount(6, $groups);
        $this->assertSame('new_arrivals', $groups[0]->source);
        foreach (array_values($members) as $index => $product) {
            $this->assertSame([$product->getKey()], $groups[$index + 1]->products->modelKeys());
        }
        foreach ($groups as $group) {
            $this->assertNotContains($unavailable->getKey(), $group->products->modelKeys());
        }
        $collection = ProductCollection::where('slug', 'broad-select-handpicked-equipment')->firstOrFail();
        $collection->update(['name' => 'Admin picks', 'is_published' => false]);
        $collection->products()->detach();
        $collection->sections()->first()->update(['is_active' => false, 'sort_order' => 9]);
        $this->seed(BroadSelectSeeder::class);
        $this->assertDatabaseCount('promo_sections', 6);
        $this->assertDatabaseCount('product_collections', 5);
        $this->assertSame('Admin picks', $collection->fresh()->name);
        $this->assertFalse($collection->fresh()->is_published);
        $this->assertSame(0, $collection->products()->count());
        $this->assertFalse($collection->sections()->first()->is_active);
        $this->assertSame(9, $collection->sections()->first()->sort_order);
    }

    public function test_existing_broad_select_configuration_is_left_alone(): void
    {
        $this->product();
        PromoSection::create([
            'placement' => config('promotions.broad_select.placement'),
            'source' => 'new_arrivals', 'layout' => 'row', 'is_active' => false,
        ]);
        $this->seed(BroadSelectSeeder::class);
        $this->assertDatabaseCount('promo_sections', 1);
        $this->assertDatabaseCount('product_collections', 0);
    }
}
