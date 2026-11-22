<?php

namespace Tests\Feature\Promotions;

use App\Models\BannerGroup;
use App\Models\HomepageSystemBlock;
use App\Models\ProductCollection;
use App\Models\User;
use App\Services\Promotions\HomepageLayoutService;
use Database\Seeders\BannerGroupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class HomepageLayoutTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function fixtures(): array
    {
        $this->seed(BannerGroupSeeder::class);
        $first = BannerGroup::where('slug', 'demo-home-discoveries')->firstOrFail();
        $second = BannerGroup::where('slug', 'demo-care-duo')->firstOrFail();
        $second->placements()->create(['placement' => 'homepage_main', 'sort_order' => 1]);
        $collection = ProductCollection::factory()->published()->create();
        $collection->products()->attach($this->product('Deferred homepage product'));
        $section = $collection->sections()->create(['heading' => 'Product block', 'layout' => 'grid', 'is_active' => true]);

        return [$first->placements()->sole(), $second->placements()->sole(), $section];
    }

    private function saveOrder(array $positions): void
    {
        foreach (['broad_select', 'category_discovery'] as $kind) {
            if (! array_key_exists('system:'.$kind, $positions)) {
                $positions['system:'.$kind] = max($positions) + 1;
            }
        }
        $revision = $this->get(route('admin.homepage-layout.index'))->assertOk()->viewData('revision');
        $this->put(route('admin.homepage-layout.update'), compact('positions', 'revision'))
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.homepage-layout.index'));
    }

    public function test_admin_can_interleave_blocks_while_product_cards_remain_deferred(): void
    {
        [$first, $second, $section] = $this->fixtures();
        $a = 'data-homepage-block="banner:'.$first->getKey().'"';
        $b = 'data-homepage-block="banner:'.$second->getKey().'"';
        $p = 'data-homepage-block="promo:'.$section->getKey().'"';
        $this->get(route('home'))->assertOk()->assertSeeInOrder([$a, $b, $p], false);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->saveOrder(['banner:'.$first->getKey() => 20, 'banner:'.$second->getKey() => 10, 'promo:'.$section->getKey() => 0]);
        $this->get(route('home'))->assertOk()->assertSeeInOrder([$p, $b, $a], false)
            ->assertDontSee('Deferred homepage product')->assertDontSee('fetchpriority="high"', false);
        $this->get(route('home.promo-sections'))->assertOk()->assertSee('Deferred homepage product')
            ->assertSee('data-home-promo-id="'.$section->getKey().'"', false)
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame(2, $first->fresh()->homepage_position);
        $this->assertSame(0, $section->fresh()->homepage_position);
    }

    public function test_management_requires_admin_for_reading_and_saving(): void
    {
        $this->get(route('admin.homepage-layout.index'))->assertRedirect(route('login'));
        $this->put(route('admin.homepage-layout.update'), [])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'customer']));
        $this->get(route('admin.homepage-layout.index'))->assertForbidden();
        $this->putJson(route('admin.homepage-layout.update'), [])->assertForbidden();
    }

    public function test_invalid_foreign_missing_and_duplicate_positions_are_rejected_atomically(): void
    {
        $this->fixtures();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $layout = app(HomepageLayoutService::class);
        $blocks = $layout->managedBlocks();
        $positions = $blocks->pluck('key')->flip()->all();
        $revision = $layout->revision($blocks);
        $this->putJson(route('admin.homepage-layout.update'), ['revision' => $revision, 'positions' => ['promo:999999' => 0]])
            ->assertUnprocessable()->assertJsonValidationErrors('positions');
        $this->putJson(route('admin.homepage-layout.update'), ['revision' => $revision, 'positions' => array_fill_keys(array_keys($positions), 0)])
            ->assertUnprocessable();
        $invalid = $positions;
        $invalid[array_key_first($invalid)] = -1;
        $this->putJson(route('admin.homepage-layout.update'), ['revision' => $revision, 'positions' => $invalid])
            ->assertUnprocessable();
        $this->assertSame($revision, $layout->revision($layout->managedBlocks()));
    }

    public function test_stale_forms_do_not_overwrite_a_newer_arrangement(): void
    {
        $this->fixtures();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $layout = app(HomepageLayoutService::class);
        $blocks = $layout->managedBlocks();
        $positions = $blocks->pluck('key')->reverse()->values()->flip()->all();
        $revision = $layout->revision($blocks);
        $this->put(route('admin.homepage-layout.update'), compact('positions', 'revision'))->assertSessionHasNoErrors();
        $after = $layout->revision($layout->managedBlocks());
        $this->putJson(route('admin.homepage-layout.update'), compact('positions', 'revision'))
            ->assertUnprocessable()->assertJsonValidationErrors('positions');
        $this->assertSame($after, $layout->revision($layout->managedBlocks()));
    }

    public function test_schedules_and_visibility_still_apply_and_first_visible_banner_gets_priority(): void
    {
        [$first, $second, $section] = $this->fixtures();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->saveOrder(['banner:'.$first->getKey() => 0, 'promo:'.$section->getKey() => 1, 'banner:'.$second->getKey() => 2]);
        $first->group->update(['expires_at' => now()]);
        $section->update(['is_active' => false]);
        $this->get(route('home'))->assertOk()
            ->assertDontSee('data-homepage-block="banner:'.$first->getKey().'"', false)
            ->assertDontSee('data-homepage-block="promo:'.$section->getKey().'"', false)
            ->assertSee('data-homepage-block="banner:'.$second->getKey().'"', false)
            ->assertSee('fetchpriority="high"', false);
        $this->get(route('admin.homepage-layout.index'))->assertOk()->assertSee('Product block');
    }

    public function test_shared_positions_are_applied_before_public_limits(): void
    {
        [$first, $second, $section] = $this->fixtures();
        $later = $section->collection->sections()->create(['heading' => 'First by shared position', 'layout' => 'grid', 'is_active' => true, 'sort_order' => 100]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->saveOrder(['banner:'.$first->getKey() => 3, 'banner:'.$second->getKey() => 1,
            'promo:'.$section->getKey() => 2, 'promo:'.$later->getKey() => 0]);
        config(['promotions.max_sections' => 1, 'banners.max_groups_per_placement' => 1]);
        $this->get(route('home'))->assertOk()
            ->assertSee('data-homepage-block="promo:'.$later->getKey().'"', false)
            ->assertSee('data-homepage-block="banner:'.$second->getKey().'"', false)
            ->assertDontSee('data-homepage-block="promo:'.$section->getKey().'"', false);
        $this->get(route('home.promo-sections'))->assertOk()->assertSee('First by shared position')->assertDontSee('Product block');
    }

    public function test_interactive_sections_can_move_between_banners_and_products_without_duplicates(): void
    {
        [$first, $second, $section] = $this->fixtures();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->saveOrder([
            'system:category_discovery' => 0, 'banner:'.$first->getKey() => 1,
            'system:broad_select' => 2, 'promo:'.$section->getKey() => 3,
            'banner:'.$second->getKey() => 4,
        ]);
        $response = $this->get(route('home'))->assertOk()->assertSeeInOrder([
            'data-homepage-block="system:category_discovery"',
            'data-homepage-block="banner:'.$first->getKey().'"',
            'data-homepage-block="system:broad_select"',
            'data-homepage-block="promo:'.$section->getKey().'"',
            'data-homepage-block="banner:'.$second->getKey().'"',
        ], false)->assertDontSee('fetchpriority="high"', false)->assertDontSee('Deferred homepage product');
        $html = $response->getContent();
        $this->assertSame(1, preg_match_all('/\sdata-category-discovery(?:\s|>)/', $html));
        $this->assertSame(1, preg_match_all('/\sdata-deferred-broad-select(?:=|\s|>)/', $html));
        $this->assertSame(0, HomepageSystemBlock::where('block_key', 'category_discovery')->sole()->homepage_position);
        $this->assertSame(2, HomepageSystemBlock::where('block_key', 'broad_select')->sole()->homepage_position);
    }

    public function test_interactive_blocks_exist_without_catalog_data_and_get_requests_do_not_write_positions(): void
    {
        HomepageSystemBlock::query()->delete();
        $this->assertCount(2, app(HomepageLayoutService::class)->systemBlocks());
        $this->get(route('home'))->assertOk()->assertSeeInOrder([
            'data-homepage-block="system:broad_select"', 'data-homepage-block="system:category_discovery"',
        ], false);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->withSession(['locale' => 'vi'])->get(route('admin.homepage-layout.index'))->assertOk()
            ->assertSee('Broad Select · carousel sản phẩm')->assertSee('Khám phá danh mục · chọn sản phẩm bằng AJAX');
        $this->assertDatabaseCount('homepage_system_blocks', 0);
        $this->saveOrder(['system:category_discovery' => 0, 'system:broad_select' => 1]);
        $this->assertDatabaseCount('homepage_system_blocks', 2);
    }

    public function test_unknown_system_blocks_cannot_be_submitted(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $layout = app(HomepageLayoutService::class);
        $blocks = $layout->managedBlocks();
        $revision = $layout->revision($blocks);
        $this->putJson(route('admin.homepage-layout.update'), [
            'revision' => $revision,
            'positions' => ['system:broad_select' => 0, 'system:category_discovery' => 1, 'system:unknown' => 2],
        ])->assertUnprocessable()->assertJsonValidationErrors('positions');
        $this->assertSame($revision, $layout->revision($layout->managedBlocks()));
    }
}
