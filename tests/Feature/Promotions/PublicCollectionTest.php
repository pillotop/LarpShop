<?php

namespace Tests\Feature\Promotions;

use App\Models\ProductCollection;
use App\Queries\Promotions\PromoSectionQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class PublicCollectionTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_only_published_collections_can_be_viewed_by_slug(): void
    {
        $draft = ProductCollection::factory()->create();
        $this->get(route('collections.show', $draft->slug))->assertNotFound();
        $this->get(route('collections.show', 'not-a-collection'))->assertNotFound();
        $draft->update(['is_published' => true]);
        $this->get(route('collections.show', $draft->slug))->assertOk()->assertSee('no products available');
    }

    public function test_public_page_preserves_member_order_and_pagination(): void
    {
        config(['promotions.collection_page_size' => 2]);
        $collection = ProductCollection::factory()->published()->create();
        $first = $this->product('First visible');
        $second = $this->product('Second visible');
        $third = $this->product('Third visible');
        $collection->products()->attach([
            $first->getKey() => ['sort_order' => 2],
            $second->getKey() => ['sort_order' => 0],
            $third->getKey() => ['sort_order' => 1],
        ]);
        $url = route('collections.show', $collection->slug);
        $response = $this->get($url)->assertOk()->assertSeeInOrder(['Second visible', 'Third visible'])
            ->assertDontSee('First visible');
        $this->assertSame(3, $response->viewData('products')->total());
        $this->get($url.'?page=2')->assertOk()->assertSee('First visible')->assertDontSee('Second visible');
        $this->getJson($url.'?page=-1')->assertUnprocessable()->assertJsonValidationErrors('page');
    }

    public function test_public_collection_excludes_hidden_products_but_can_show_out_of_stock_products(): void
    {
        $collection = ProductCollection::factory()->published()->create();
        $visible = $this->product('Visible item', stock: 0);
        $hidden = $this->product('Hidden item');
        $hidden->update(['is_active' => false]);
        $pending = $this->product('Pending item');
        $pending->update(['status' => 'pending']);
        $suspended = $this->product('Suspended item');
        $suspended->vendor->update(['status' => 'suspended']);
        $collection->products()->attach([$visible->getKey(), $hidden->getKey(), $pending->getKey(), $suspended->getKey()]);

        $this->get(route('collections.show', $collection->slug))->assertOk()
            ->assertSee('Visible item')->assertSee('Out of Stock')
            ->assertDontSee('Hidden item')->assertDontSee('Pending item')->assertDontSee('Suspended item');
    }

    public function test_collection_page_has_cart_capabilities_breadcrumbs_and_currency_formatting(): void
    {
        Http::preventStrayRequests();
        Http::fake(['open.er-api.com/*' => Http::response([
            'result' => 'success', 'base_code' => 'USD', 'rates' => ['VND' => 25000],
        ])]);
        $collection = ProductCollection::factory()->published()->create(['name' => 'Holiday choices']);
        $product = $this->product(price: '12.50');
        $collection->products()->attach($product);
        $this->withSession(['display_currency' => 'VND', 'locale' => 'vi'])
            ->get(route('collections.show', $collection->slug))->assertOk()
            ->assertSee('312.500 ₫')->assertSee('Holiday choices')
            ->assertSee('id="cart-initial-state"', false)->assertSee('data-cart-show-url=', false)
            ->assertSee('data-add-to-cart-url=', false)->assertSee('aria-current="page"', false);
        $this->postJson(route('cart.add'), ['product_id' => $product->getKey(), 'quantity' => 1])
            ->assertOk()->assertJsonPath('cart.total_amount', '12.50')
            ->assertJsonPath('cart.formatted_total', '312.500 ₫');
    }

    public function test_public_content_is_escaped_and_promo_links_resolve_to_the_collection(): void
    {
        $collection = ProductCollection::factory()->published()->create([
            'name' => '<script>alert(1)</script>', 'description' => '<img src=x onerror=alert(1)>',
        ]);
        $collection->products()->attach($this->product());
        $url = route('collections.show', $collection->slug);
        $this->get($url)->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('<img src=x onerror=alert(1)>', false);
        $data = app(PromoSectionQuery::class)->preview($collection, 'grid');
        $html = Blade::render('<x-promo-section :data="$data" />', compact('data'));
        $this->assertStringContainsString('href="'.$url.'"', $html);
        $html = Blade::render('<x-promo-section :data="$data" :preview="true" />', compact('data'));
        $this->assertStringNotContainsString('href="'.$url.'"', $html);
    }
}
