<?php

namespace Tests\Feature\Promotions;

use App\Enums\PromoSource;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\ProductReview;
use App\Models\PromoSection;
use App\Models\User;
use App\Queries\Products\ProductCatalogQuery;
use App\Queries\Promotions\PromoSectionQuery;
use App\Services\Promotions\BroadSelectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class BroadSelectSourceTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function section(PromoSource $source, array $overrides = []): PromoSection
    {
        return PromoSection::forceCreate(array_merge([
            'source' => $source->value, 'product_collection_id' => null,
            'placement' => config('promotions.broad_select.placement'), 'layout' => 'row', 'is_active' => true,
        ], $overrides));
    }

    private function rate(Product $product, array $ratings): void
    {
        foreach ($ratings as $rating) {
            ProductReview::forceCreate([
                'product_id' => $product->getKey(), 'user_id' => User::factory()->create()->getKey(), 'rating' => $rating,
            ]);
        }
    }

    public function test_top_rated_requires_enough_reviews_and_has_deterministic_tie_breaks(): void
    {
        $tooFew = $this->product('One perfect review');
        $this->rate($tooFew, [5]);
        $first = $this->product('First rated');
        $this->rate($first, [4, 4, 4]);
        $second = $this->product('Second rated');
        $this->rate($second, [4, 4, 4]);
        $moreReviews = $this->product('More reviews');
        $this->rate($moreReviews, [4, 4, 4, 4]);
        $best = $this->product('Best rated');
        $this->rate($best, [5, 5, 5]);
        $query = app(ProductCatalogQuery::class);
        $ranked = $query->automaticPromoProducts(PromoSource::TopRated);
        $this->assertSame([$best->getKey(), $moreReviews->getKey(), $second->getKey(), $first->getKey()], $ranked->modelKeys());
        config(['promotions.broad_select.minimum_reviews' => 1]);
        $this->assertContains($tooFew->getKey(), $query->automaticPromoProducts(PromoSource::TopRated)->modelKeys());
    }

    public function test_new_arrivals_uses_creation_time_and_custom_key_and_bounds_the_result(): void
    {
        $old = $this->product('Old product');
        $old->update(['created_at' => now()->subYear()]);
        $first = $this->product('First new product');
        $last = $this->product('Last new product');
        $first->update(['created_at' => now()]);
        $last->update(['created_at' => $first->created_at]);
        config(['promotions.broad_select.product_limit' => 2]);
        $query = app(ProductCatalogQuery::class);
        $products = $query->automaticPromoProducts(PromoSource::NewArrivals, 999);
        $this->assertSame([$last->getKey(), $first->getKey()], $products->modelKeys());
        $this->assertCount(1, $query->automaticPromoProducts(PromoSource::NewArrivals, 0));
        foreach ($products as $product) {
            foreach (['images', 'category', 'vendor'] as $relation) {
                $this->assertTrue($product->relationLoaded($relation));
            }
            $this->assertNotNull($product->total_reviews);
            $this->assertFalse($product->relationLoaded('reviews'));
        }
    }

    public function test_both_automatic_sources_share_catalog_visibility(): void
    {
        $visible = $this->product('Visible');
        $this->rate($visible, [4, 4, 4]);
        foreach (['hidden', 'pending', 'rejected', 'sold-out', 'suspended-vendor'] as $reason) {
            $product = $this->product($reason);
            $this->rate($product, [5, 5, 5]);
            match ($reason) {
                'hidden' => $product->update(['is_active' => false]),
                'pending' => $product->update(['status' => Product::STATUS_PENDING]),
                'rejected' => $product->update(['status' => Product::STATUS_REJECTED]),
                'sold-out' => $product->update(['stock' => 0]),
                'suspended-vendor' => $product->vendor->update(['status' => 'suspended']),
            };
        }
        foreach ([PromoSource::TopRated, PromoSource::NewArrivals] as $source) {
            $this->assertSame([$visible->getKey()], app(ProductCatalogQuery::class)->automaticPromoProducts($source)->modelKeys());
        }
    }

    public function test_published_collection_keeps_manual_order_and_draft_is_not_a_public_source(): void
    {
        $first = $this->product('First member');
        $second = $this->product('Second member');
        $this->product('Outside collection');
        $collection = ProductCollection::factory()->published()->create();
        $collection->products()->attach([$first->getKey() => ['sort_order' => 2], $second->getKey() => ['sort_order' => 1]]);
        $section = $collection->sections()->create(['placement' => config('promotions.broad_select.placement'), 'layout' => 'row', 'is_active' => true]);
        $this->assertSame('collection', $section->fresh()->source);
        $groups = app(BroadSelectService::class)->forHomepage();
        $this->assertSame([$second->getKey(), $first->getKey()], $groups->first()->products->modelKeys());
        $this->assertSame(route('collections.show', $collection->slug), $groups->first()->viewAllUrl);
        $collection->update(['is_published' => false]);
        $this->assertCount(0, app(BroadSelectService::class)->forHomepage());
    }

    public function test_empty_sources_are_excluded_before_group_limit_and_scheduling_is_exact(): void
    {
        $this->travelTo(now()->startOfSecond());
        config(['promotions.broad_select.max_groups' => 1]);
        $this->product('New arrival');
        $this->section(PromoSource::TopRated, ['sort_order' => 0]);
        $active = $this->section(PromoSource::NewArrivals, ['starts_at' => now(), 'expires_at' => now()->addSecond(), 'sort_order' => 1]);
        $future = $this->section(PromoSource::NewArrivals, ['starts_at' => now()->addSecond(), 'sort_order' => 2]);
        $this->section(PromoSource::NewArrivals, ['is_active' => false, 'sort_order' => 0]);
        $this->section(PromoSource::NewArrivals, ['expires_at' => now(), 'sort_order' => 0]);
        $service = app(BroadSelectService::class);
        $this->assertSame('promo-'.$active->getKey(), $service->forHomepage()->first()->key);
        $this->assertSame('promo-'.$future->getKey(), $service->forHomepage(now()->addSecond())->first()->key);
    }

    public function test_unknown_sources_and_malformed_associations_are_ignored_and_automatic_sources_stay_outside_main(): void
    {
        $this->product('Visible');
        $this->section(PromoSource::NewArrivals, ['source' => 'arbitrary-query']);
        $this->section(PromoSource::NewArrivals, ['product_collection_id' => ProductCollection::factory()->published()->create()->getKey()]);
        $this->section(PromoSource::Collection);
        $this->section(PromoSource::NewArrivals, ['placement' => 'homepage_main']);
        $this->assertCount(0, app(BroadSelectService::class)->forHomepage());
        $this->assertCount(0, app(PromoSectionQuery::class)->visibleForPlacement('homepage_main'));
    }

    public function test_automatic_groups_load_once_per_source_and_render_without_lazy_queries(): void
    {
        $product = $this->product('Prepared automatic product');
        $this->rate($product, [5, 5, 5]);
        for ($index = 0; $index < 3; $index++) {
            $this->section(PromoSource::TopRated);
            $this->section(PromoSource::NewArrivals);
        }
        Blade::render('<span>{{ $money->format(1) }}</span>');
        DB::enableQueryLog();
        DB::flushQueryLog();
        try {
            $data = app(PromoSectionQuery::class)->visibleForPlacement(config('promotions.broad_select.placement'));
            $this->assertCount(6, $data);
            $this->assertLessThanOrEqual(9, count(DB::getQueryLog()));
            DB::flushQueryLog();
            $html = Blade::render('@foreach ($data as $section)<x-promo-section :data="$section" />@endforeach', compact('data'));
            $this->assertStringContainsString('Prepared automatic product', $html);
            $this->assertCount(0, DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }

    public function test_homepage_renders_each_configured_group_with_its_own_ordered_products(): void
    {
        $rated = $this->product('Older rated product');
        $rated->update(['created_at' => now()->subYear()]);
        $this->rate($rated, [5, 5, 5]);
        $recent = $this->product('Recent collection product');
        $collection = ProductCollection::factory()->published()->create();
        $collection->products()->attach($recent->getKey());
        $manual = $this->section(PromoSource::Collection, [
            'product_collection_id' => $collection->getKey(), 'heading' => 'Manual picks', 'sort_order' => 0,
        ]);
        $top = $this->section(PromoSource::TopRated, ['sort_order' => 1]);
        $new = $this->section(PromoSource::NewArrivals, ['sort_order' => 2]);
        $this->section(PromoSource::NewArrivals, ['heading' => 'Future group', 'starts_at' => now()->addDay()]);
        $response = $this->get(route('home.broad-select'))->assertOk()
            ->assertDontSee('Future group')
            ->assertDontSee('<script', false)
            ->assertDontSee('/build/', false);
        $groups = $response->viewData('broadSelectGroups');
        $this->assertSame(['promo-'.$manual->getKey(), 'promo-'.$top->getKey(), 'promo-'.$new->getKey()], $groups->pluck('key')->all());
        $this->assertSame([$recent->getKey()], $groups[0]->products->modelKeys());
        $this->assertSame([$rated->getKey()], $groups[1]->products->modelKeys());
        $this->assertSame([$recent->getKey(), $rated->getKey()], $groups[2]->products->modelKeys());
        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML($response->getContent());
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new \DOMXPath($document);
        foreach ($groups as $index => $group) {
            $panel = $xpath->query('//*[@data-broad-select-panel="'.$group->key.'"]')->item(0);
            $this->assertNotNull($panel);
            $this->assertSame($index !== 0, $panel->hasAttribute('hidden'));
            $cards = $xpath->query('.//*[@data-product-id]', $panel);
            $this->assertSame($group->products->modelKeys(), array_map(
                fn ($card) => (int) $card->getAttribute('data-product-id'), iterator_to_array($cards),
            ));
        }
        $this->assertSame(1, $xpath->query('//*[@data-broad-select-panel]//a[@href="'.route('collections.show', $collection->slug).'"]')->length);
    }

    public function test_repeated_products_keep_cart_and_wishlist_actions_in_each_group(): void
    {
        $product = $this->product('Shared promo product');
        $customer = User::factory()->create();
        $this->section(PromoSource::NewArrivals);
        $this->section(PromoSource::NewArrivals);
        $this->actingAs($customer)->post(route('wishlist.store', $product))->assertRedirect();
        $response = $this->get(route('home.broad-select'))->assertOk();
        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML($response->getContent());
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new \DOMXPath($document);
        $panels = $xpath->query('//*[@data-broad-select-panel]');
        $this->assertSame(2, $panels->length);
        foreach ($panels as $panel) {
            $this->assertSame(1, $xpath->query('.//*[@data-product-id="'.$product->getKey().'"]//button[@data-add-to-cart]', $panel)->length);
            $this->assertSame(1, $xpath->query('.//form[@action="'.route('wishlist.destroy', $product).'"]//button[@aria-pressed="true"]', $panel)->length);
        }
        $this->postJson(route('cart.add'), ['product_id' => $product->getKey(), 'quantity' => 1])->assertOk();
        $this->assertDatabaseHas('cart_items', ['itemable_id' => $product->getKey(), 'quantity' => 1]);
    }

    public function test_null_collection_metadata_is_translated_and_does_not_break_admin_listing(): void
    {
        $this->product('New arrival');
        $section = $this->section(PromoSource::NewArrivals);
        app()->setLocale('vi');
        $group = app(BroadSelectService::class)->forHomepage()->first();
        $this->assertSame(__('promotions.sources.new_arrivals'), $group->label);
        $this->assertNull($group->viewAllUrl);
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.sections.index'))->assertOk()->assertSee($group->label);
        $this->assertNull($section->fresh()->product_collection_id);
    }
}
