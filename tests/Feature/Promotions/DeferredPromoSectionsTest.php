<?php

namespace Tests\Feature\Promotions;

use App\Models\ProductCollection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class DeferredPromoSectionsTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    public function test_initial_homepage_loads_only_promo_metadata_and_defers_product_cards(): void
    {
        $product = $this->product('Deferred product card');
        $collection = ProductCollection::factory()->published()->create();
        $collection->products()->attach($product);
        $section = $collection->sections()->create(['is_active' => true, 'layout' => 'grid']);
        $this->get(route('home'))->assertOk()
            ->assertSee('data-deferred-promo-sections', false)
            ->assertSee('data-homepage-promo-id="'.$section->getKey().'"', false)
            ->assertSee(route('home.promo-sections'), false)
            ->assertDontSee('Deferred product card');
    }

    public function test_empty_placement_returns_no_content(): void
    {
        $this->get(route('home.promo-sections'))->assertNoContent();
    }

    public function test_all_layouts_are_rendered_without_assets_and_preserve_customer_actions(): void
    {
        $product = $this->product('Deferred choice');
        $collection = ProductCollection::factory()->published()->create();
        $collection->products()->attach($product);
        foreach (['grid', 'row', 'split'] as $position => $layout) {
            $collection->sections()->create([
                'layout' => $layout, 'heading' => ucfirst($layout).' selection',
                'sort_order' => $position, 'is_active' => true,
            ]);
        }
        $customer = User::factory()->create();
        $this->actingAs($customer)->post(route('wishlist.store', $product))->assertRedirect();
        $response = $this->withSession(['locale' => 'vi'])
            ->get(route('home.promo-sections', ['placement' => 'homepage_broad_select', 'limit' => 999]))
            ->assertOk()
            ->assertSeeInOrder(['Grid selection', 'Row selection', 'Split selection'])
            ->assertSee('data-promo-layout="grid"', false)
            ->assertSee('data-promo-layout="row"', false)
            ->assertSee('data-promo-layout="split"', false)
            ->assertSee('data-scroll-next', false)
            ->assertSee('data-add-to-cart', false)
            ->assertSee(route('wishlist.destroy', $product), false)
            ->assertSee('Thêm vào giỏ hàng')
            ->assertDontSee('<script', false)
            ->assertDontSee('/build/', false);
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }
}
