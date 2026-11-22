<?php

namespace Tests\Feature\Banners;

use App\Models\BannerCard;
use App\Models\BannerGroup;
use App\Models\Category;
use App\Models\PromoSection;
use App\Queries\Banners\BannerGroupQuery;
use Database\Seeders\BannerGroupSeeder;
use Database\Seeders\BroadSelectSeeder;
use Database\Seeders\PromoSectionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class BannerGroupSeederTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    public function test_bundled_groups_have_complete_layouts_real_responsive_assets_and_localized_copy(): void
    {
        Category::create(['name' => 'Electronics', 'slug' => 'electronics', 'is_active' => true]);
        $this->seed(BannerGroupSeeder::class);
        $this->assertDatabaseCount('banner_groups', 5);
        $this->assertDatabaseCount('banner_cards', 17);
        $this->assertDatabaseCount('banner_group_placements', 1);
        $query = app(BannerGroupQuery::class);
        $this->assertSame(['demo-home-discoveries'], $query->visibleForPlacement('homepage_main')->pluck('slug')->all());
        $this->assertCount(5, $query->visibleByIds(BannerGroup::pluck('banner_group_id')->all()));

        foreach (BannerGroup::with('cards')->get() as $group) {
            $this->assertCount(config('banners.layouts.'.$group->layout.'.card_count'), $group->cards);
            foreach ($group->cards as $card) {
                $this->assertNotSame($card->title, $card->localizedText('title', 'vi'));
                $this->assertSame('public_assets', $card->image_disk);
                foreach ([['path' => $card->image_path, 'width' => $card->image_width, 'height' => $card->image_height], ...$card->image_variants] as $variant) {
                    $size = getimagesize(public_path('images/'.$variant['path']));
                    $this->assertSame([$variant['width'], $variant['height']], [$size[0], $size[1]]);
                    $this->assertSame('image/webp', $size['mime']);
                }
            }
        }
        $this->assertTrue(BannerCard::where('link_url', '/products?category=electronics')->exists());
        $this->assertTrue(BannerCard::where('link_url', '/products')->exists());
    }

    public function test_rerun_preserves_admin_edits_missing_cards_and_removed_placements(): void
    {
        $this->seed(BannerGroupSeeder::class);
        $group = BannerGroup::where('slug', 'demo-home-discoveries')->firstOrFail();
        $group->update(['name' => 'Admin campaign', 'is_active' => false, 'expires_at' => now()->subDay()]);
        $card = $group->cards()->first();
        $card->update(['title' => 'Edited title', 'link_url' => '/products?q=custom']);
        $group->cards()->whereKeyNot($card->getKey())->delete();
        $group->placements()->delete();
        $this->seed(BannerGroupSeeder::class);
        $this->assertDatabaseCount('banner_groups', 5);
        $this->assertDatabaseCount('banner_cards', 14);
        $this->assertDatabaseCount('banner_group_placements', 0);
        $this->assertSame('Admin campaign', $group->fresh()->name);
        $this->assertFalse($group->fresh()->is_active);
        $this->assertTrue($group->fresh()->expires_at->isPast());
        $this->assertSame('Edited title', $card->fresh()->title);
        $this->assertSame('/products?q=custom', $card->fresh()->link_url);
    }

    public function test_existing_homepage_assignment_is_not_displaced_or_expanded(): void
    {
        $existing = BannerGroup::create(['slug' => 'admin-home', 'name' => 'Admin home', 'layout' => 'duo', 'is_active' => false]);
        $existing->placements()->create(['placement' => 'homepage_main', 'sort_order' => 9]);
        $this->seed(BannerGroupSeeder::class);
        $this->assertDatabaseCount('banner_group_placements', 1);
        $this->assertSame(9, $existing->placements()->first()->sort_order);
        $this->assertSame(0, BannerGroup::where('slug', 'demo-home-discoveries')->first()->placements()->count());
    }

    public function test_new_promo_samples_attach_groups_but_reruns_do_not_restore_detached_banners(): void
    {
        $this->product('Sample product');
        $this->seed([BannerGroupSeeder::class, PromoSectionSeeder::class, BroadSelectSeeder::class]);
        $featured = PromoSection::where('heading', 'Featured finds')->firstOrFail();
        $arrivals = PromoSection::where('source', 'new_arrivals')->firstOrFail();
        $this->assertSame('demo-care-duo', $featured->bannerGroup->slug);
        $this->assertSame('demo-everyday-trio', $arrivals->bannerGroup->slug);
        $count = PromoSection::count();
        $featured->bannerGroup()->dissociate()->save();
        $arrivals->bannerGroup()->dissociate()->save();
        $this->seed([BannerGroupSeeder::class, PromoSectionSeeder::class, BroadSelectSeeder::class]);
        $this->assertSame($count, PromoSection::count());
        $this->assertNull($featured->fresh()->banner_group_id);
        $this->assertNull($arrivals->fresh()->banner_group_id);
    }

    public function test_existing_promo_configuration_is_not_modified_when_banners_are_seeded_later(): void
    {
        $this->product('Existing sample');
        $this->seed([PromoSectionSeeder::class, BroadSelectSeeder::class]);
        $before = PromoSection::orderBy('promo_section_id')->get()->toArray();
        $this->seed([BannerGroupSeeder::class, PromoSectionSeeder::class, BroadSelectSeeder::class]);
        $this->assertSame($before, PromoSection::orderBy('promo_section_id')->get()->toArray());
    }

    public function test_seeded_layouts_render_in_both_languages_with_responsive_images(): void
    {
        $this->withoutVite();
        $this->seed(BannerGroupSeeder::class);
        foreach (['en', 'vi'] as $locale) {
            app()->setLocale($locale);
            foreach (BannerGroup::with('cards')->get() as $group) {
                $html = Blade::render('<x-banner-group :group="$group" :load-assets="false" />', compact('group'));
                $this->assertStringContainsString(e($group->cards->first()->localizedText('title')), $html);
                $this->assertSame($group->cards->count(), substr_count($html, 'srcset='));
                $this->assertStringNotContainsString('fetchpriority="high"', $html);
            }
        }
    }
}
