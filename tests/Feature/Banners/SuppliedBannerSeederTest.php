<?php

namespace Tests\Feature\Banners;

use App\Models\BannerCard;
use App\Models\BannerGroup;
use App\Models\Category;
use App\Queries\Banners\BannerGroupQuery;
use Database\Seeders\BannerGroupSeeder;
use Database\Seeders\SuppliedBannerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class SuppliedBannerSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_eleven_images_are_imported_as_complete_localized_reusable_groups(): void
    {
        $this->withoutVite();
        Category::create(['name' => 'Electronics', 'slug' => 'electronics', 'is_active' => true]);
        $this->seed(SuppliedBannerSeeder::class);
        $this->assertDatabaseCount('banner_groups', 4);
        $this->assertDatabaseCount('banner_cards', 11);
        $this->assertDatabaseCount('banner_group_placements', 0);
        $this->assertCount(4, app(BannerGroupQuery::class)->visibleByIds(BannerGroup::pluck('banner_group_id')->all()));
        $this->assertSame(11, BannerCard::distinct()->count('image_path'));
        $this->assertTrue(BannerCard::where('link_url', '/products?category=electronics')->exists());
        $this->assertTrue(BannerCard::where('link_url', '/products')->exists());

        foreach (BannerGroup::with('cards')->get() as $group) {
            $this->assertCount(config('banners.layouts.'.$group->layout.'.card_count'), $group->cards);
            foreach ($group->cards as $card) {
                $this->assertSame('public_assets', $card->image_disk);
                $this->assertCount(2, $card->image_variants);
                foreach ([['path' => $card->image_path, 'width' => $card->image_width, 'height' => $card->image_height], ...$card->image_variants] as $image) {
                    $size = getimagesize(public_path('images/'.$image['path']));
                    $this->assertSame([$image['width'], $image['height']], [$size[0], $size[1]]);
                    $this->assertSame('image/webp', $size['mime']);
                }
            }
            foreach (['en', 'vi'] as $locale) {
                app()->setLocale($locale);
                $html = Blade::render('<x-banner-group :group="$group" :load-assets="false" />', compact('group'));
                $this->assertStringContainsString(e($group->cards->first()->localizedText('title')), $html);
                $this->assertSame($group->cards->count(), substr_count($html, 'srcset='));
            }
        }
    }

    public function test_reruns_preserve_admin_edits_and_existing_homepage_content(): void
    {
        $this->seed(BannerGroupSeeder::class);
        $home = BannerGroup::where('slug', 'demo-home-discoveries')->firstOrFail();
        $before = $home->load(['cards', 'placements'])->toArray();
        $this->seed(SuppliedBannerSeeder::class);
        $group = BannerGroup::where('slug', 'supplied-everyday-upgrades')->firstOrFail();
        $group->update(['name' => 'Edited campaign', 'is_active' => false]);
        $card = $group->cards()->first();
        $card->update(['title' => 'Edited headline', 'link_url' => '/products?q=custom']);
        $group->cards()->whereKeyNot($card->getKey())->delete();
        $this->seed(SuppliedBannerSeeder::class);
        $this->assertDatabaseCount('banner_groups', 9);
        $this->assertDatabaseCount('banner_cards', 26);
        $this->assertDatabaseCount('banner_group_placements', 1);
        $this->assertSame($before, $home->fresh()->load(['cards', 'placements'])->toArray());
        $this->assertSame('Edited campaign', $group->fresh()->name);
        $this->assertFalse($group->fresh()->is_active);
        $this->assertSame('Edited headline', $card->fresh()->title);
        $this->assertSame('/products?q=custom', $card->fresh()->link_url);
    }
}
