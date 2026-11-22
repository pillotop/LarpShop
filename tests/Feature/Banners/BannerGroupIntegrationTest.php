<?php

namespace Tests\Feature\Banners;

use App\Models\BannerGroup;
use App\Models\ProductCollection;
use App\Models\PromoSection;
use App\Models\User;
use App\Queries\Promotions\PromoSectionQuery;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class BannerGroupIntegrationTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function group(string $slug = 'home-picks', array $attributes = []): BannerGroup
    {
        $group = BannerGroup::create(array_merge([
            'slug' => $slug, 'name' => ucfirst($slug), 'layout' => 'duo', 'is_active' => true,
        ], $attributes));
        for ($index = 0; $index < 2; $index++) {
            $group->cards()->create([
                'title' => $slug.' banner '.$index, 'alt_text' => 'Collection image',
                'image_disk' => 'public_assets', 'image_path' => 'aestheticjourney-cream-1x1.jpg',
                'image_width' => 1920, 'image_height' => 1920, 'link_url' => '/products', 'sort_order' => $index,
            ]);
        }

        return $group;
    }

    private function section(?BannerGroup $group = null, array $attributes = []): PromoSection
    {
        $collection = ProductCollection::factory()->published()->create();
        $collection->products()->attach($this->product('Curated product '.$collection->getKey()));
        $section = $collection->sections()->create(array_merge(['is_active' => true, 'heading' => 'Curated picks'], $attributes));
        if ($group) {
            $section->bannerGroup()->associate($group)->save();
        }

        return $section;
    }

    public function test_homepage_renders_database_banners_in_order_and_prioritizes_only_the_first_image(): void
    {
        $second = $this->group('second');
        $second->placements()->create(['placement' => 'homepage_main', 'sort_order' => 5]);
        $first = $this->group('first');
        $first->placements()->create(['placement' => 'homepage_main', 'sort_order' => 1]);
        $this->group('unassigned');
        $draft = $this->group('draft', ['is_active' => false]);
        $draft->placements()->create(['placement' => 'homepage_main']);
        $response = $this->get(route('home'))->assertOk()->assertSeeInOrder(['first banner 0', 'second banner 0'])
            ->assertDontSee('draft banner')->assertDontSee('unassigned banner')->assertDontSee('category-banner__overlay', false);
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML($response->getContent());
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($document);
        $this->assertSame(2, $xpath->query('//*[@data-homepage-layout]/*[starts-with(@data-homepage-block, "banner:")]//section')->length);
        $this->assertSame(1, $xpath->query('//*[@data-homepage-layout]//img[@fetchpriority="high" and @loading="eager"]')->length);
        $this->assertSame(3, $xpath->query('//*[@data-homepage-layout]//img[@loading="lazy"]')->length);
    }

    public function test_empty_and_expired_homepage_groups_leave_no_empty_banner_region(): void
    {
        $this->get(route('home'))->assertOk()->assertDontSee('data-homepage-block="banner:', false);
        $group = $this->group(attributes: ['expires_at' => now()]);
        $group->placements()->create(['placement' => 'homepage_main']);
        $this->get(route('home'))->assertOk()->assertDontSee('data-homepage-block="banner:', false)
            ->assertDontSee('data-deferred-promo-sections', false);
    }

    public function test_attached_banners_render_in_both_fragments_without_injected_assets_or_eager_images(): void
    {
        $group = $this->group();
        $this->section($group);
        $this->section($group, ['placement' => 'homepage_broad_select', 'layout' => 'row']);
        foreach (['home.promo-sections', 'home.broad-select'] as $route) {
            $response = $this->get(route($route))->assertOk()->assertSee('home-picks banner 0')
                ->assertSee('Curated product')->assertSee('data-banner-layout="duo"', false)
                ->assertDontSee('fetchpriority="high"', false)->assertDontSee('/build/', false)->assertDontSee('<script', false);
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        }
    }

    public function test_hidden_or_deleted_groups_do_not_hide_their_products_and_updates_are_immediate(): void
    {
        $this->travelTo(now()->startOfDay()->addHours(12));
        $group = $this->group(attributes: ['starts_at' => now()->addSecond()]);
        $section = $this->section($group);
        $this->get(route('home.promo-sections'))->assertOk()->assertSee('Curated product')->assertDontSee('home-picks banner');
        $this->travel(1)->seconds();
        $this->get(route('home.promo-sections'))->assertSee('home-picks banner 0');
        $group->cards()->first()->update(['title' => 'Updated immediately']);
        $this->get(route('home.promo-sections'))->assertSee('Updated immediately');
        $group->update(['expires_at' => now()]);
        $this->get(route('home.promo-sections'))->assertSee('Curated product')->assertDontSee('Updated immediately');
        $group->update(['expires_at' => null]);
        $group->cards()->first()->update(['is_active' => false]);
        $this->get(route('home.promo-sections'))->assertSee('Curated product')->assertDontSee('home-picks banner');
        $group->delete();
        $this->assertNull($section->fresh()->banner_group_id);
        $this->get(route('home.promo-sections'))->assertSee('Curated product');
    }

    public function test_admin_can_attach_replace_and_clear_groups_while_old_clients_preserve_the_attachment(): void
    {
        $group = $this->group();
        $section = $this->section();
        $data = ['product_collection_id' => $section->product_collection_id, 'layout' => 'grid', 'placement' => 'homepage_main'];
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->put(route('admin.sections.update', $section), $data + ['banner_group_id' => $group->getKey()])->assertSessionHasNoErrors();
        $this->assertSame($group->getKey(), $section->fresh()->banner_group_id);
        $this->put(route('admin.sections.update', $section), $data)->assertSessionHasNoErrors();
        $this->assertSame($group->getKey(), $section->fresh()->banner_group_id);
        $this->putJson(route('admin.sections.update', $section), $data + ['banner_group_id' => 999999])->assertUnprocessable()->assertJsonValidationErrors('banner_group_id');
        $other = $this->group('other');
        $this->put(route('admin.sections.update', $section), $data + ['banner_group_id' => $other->getKey()])->assertSessionHasNoErrors();
        $this->assertSame($other->getKey(), $section->fresh()->banner_group_id);
        $this->put(route('admin.sections.update', $section), $data + ['banner_group_id' => ''])->assertSessionHasNoErrors();
        $this->assertNull($section->fresh()->banner_group_id);
    }

    public function test_editor_retains_an_older_group_choice_and_preview_honors_banner_visibility(): void
    {
        $group = $this->group(attributes: ['is_active' => false]);
        $section = $this->section($group);
        for ($index = 0; $index < 51; $index++) {
            BannerGroup::create(['slug' => 'draft-'.$index, 'name' => 'Draft '.$index, 'layout' => 'duo']);
        }
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get(route('admin.sections.edit', $section))->assertOk()->assertSee('Home-picks');
        $this->get(route('admin.promotions.preview', ['section' => $section->getKey()]))->assertOk()
            ->assertSee('Curated product')->assertDontSee('home-picks banner');
        $group->update(['is_active' => true]);
        $this->get(route('admin.promotions.preview', ['section' => $section->getKey()]))->assertOk()->assertSee('home-picks banner');
    }

    public function test_attached_groups_are_loaded_in_a_fixed_query_budget_and_render_without_database_reads(): void
    {
        for ($index = 0; $index < 8; $index++) {
            $this->section($this->group('group-'.$index));
        }
        Blade::render('<span>{{ $money->format(1) }}</span>');
        DB::enableQueryLog();
        DB::flushQueryLog();
        try {
            $prepared = app(PromoSectionQuery::class)->visibleForPlacement('homepage_main');
            $this->assertCount(8, $prepared);
            $this->assertLessThanOrEqual(8, count(DB::getQueryLog()));
            DB::flushQueryLog();
            Blade::render('@foreach ($prepared as $data)<x-promo-section :data="$data" />@endforeach', compact('prepared'));
            $this->assertCount(0, DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }
}
