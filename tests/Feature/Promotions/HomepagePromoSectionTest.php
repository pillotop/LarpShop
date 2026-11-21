<?php

namespace Tests\Feature\Promotions;

use App\Models\ProductCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class HomepagePromoSectionTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_homepage_inserts_ordered_sections_before_existing_discovery_content(): void
    {
        $collection = ProductCollection::factory()->published()->create();
        $collection->products()->attach($this->product('Homepage choice'));
        $collection->sections()->create(['is_active' => true, 'layout' => 'row', 'heading' => 'Second promotion', 'sort_order' => 5]);
        $collection->sections()->create(['is_active' => true, 'layout' => 'grid', 'heading' => 'First promotion', 'sort_order' => 1]);
        $collection->sections()->create(['is_active' => false, 'heading' => 'Inactive promotion']);
        $collection->sections()->create(['is_active' => true, 'heading' => 'Future promotion', 'starts_at' => now()->addDay()]);
        $collection->sections()->create(['is_active' => true, 'heading' => 'Expired promotion', 'expires_at' => now()]);
        $this->get(route('home'))->assertOk()->assertDontSee('First promotion')
            ->assertSeeInOrder(['data-deferred-promo-sections', 'data-deferred-broad-select']);
        $this->get(route('home.promo-sections'))->assertOk()
            ->assertSeeInOrder(['First promotion', 'Second promotion'])
            ->assertSee(route('collections.show', $collection->slug), false)
            ->assertSee('data-scroll-content', false)
            ->assertDontSee('Inactive promotion')->assertDontSee('Future promotion')->assertDontSee('Expired promotion');
    }

    public function test_homepage_keeps_legacy_content_when_collections_are_draft_or_products_unavailable(): void
    {
        $collection = ProductCollection::factory()->published()->create();
        $product = $this->product();
        $collection->products()->attach($product);
        $collection->sections()->create(['is_active' => true, 'heading' => 'Conditional promotion']);
        $this->get(route('home.promo-sections'))->assertOk()->assertSee('Conditional promotion');
        $collection->update(['is_published' => false]);
        $this->get(route('home.promo-sections'))->assertNoContent();
        $this->get(route('home'))->assertOk()->assertSee(__('products.home.heading'));
        $collection->update(['is_published' => true]);
        $product->update(['stock' => 0]);
        $this->get(route('home.promo-sections'))->assertNoContent();
        $this->get(route('home'))->assertOk()->assertSee(__('products.home.heading'));
    }
}
