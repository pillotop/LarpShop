<?php

namespace Tests\Feature\Banners;

use App\Models\BannerGroup;
use App\Models\BannerGroupPlacement;
use App\Models\ProductCollection;
use App\Models\PromoSection;
use App\Queries\Banners\BannerGroupQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BannerGroupTest extends TestCase
{
    use RefreshDatabase;

    private function group(string $slug, int $count = 2, array $attributes = []): BannerGroup
    {
        $group = BannerGroup::create(array_merge([
            'slug' => $slug, 'name' => 'Home inspiration', 'layout' => 'duo', 'is_active' => true,
        ], $attributes));
        for ($i = 0; $i < $count; $i++) {
            $group->cards()->create([
                'title' => 'Explore home', 'alt_text' => 'A furnished room',
                'image_path' => 'banners/room.webp', 'image_width' => 1000, 'image_height' => 1200,
                'link_url' => '/products', 'sort_order' => $count - $i,
            ]);
        }
        $group->placements()->create(['placement' => 'homepage_main']);

        return $group;
    }

    public function test_visibility_excludes_incomplete_extra_disabled_unknown_and_unassigned_groups_before_limiting(): void
    {
        config(['banners.max_groups_per_placement' => 1]);
        $this->group('incomplete', 1);
        $this->group('extra', 3);
        $this->group('disabled', 2, ['is_active' => false]);
        $this->group('unknown', 2, ['layout' => 'arbitrary-template']);
        $this->group('inactive-card')->cards()->first()->update(['is_active' => false]);
        $this->group('unassigned')->placements()->delete();
        $expected = $this->group('complete');

        $groups = app(BannerGroupQuery::class)->visibleForPlacement('homepage_main');
        $this->assertSame([$expected->getKey()], $groups->modelKeys());
        $this->assertTrue($groups->first()->relationLoaded('cards'));
        $this->assertSame([1, 2], $groups->first()->cards->pluck('sort_order')->all());
    }

    public function test_schedule_boundaries_and_individual_reads_match_placement_visibility(): void
    {
        $this->travelTo(now()->startOfDay()->addHours(12));
        $active = $this->group('active', 2, ['starts_at' => now(), 'expires_at' => now()->addSecond()]);
        $next = $this->group('next', 2, ['starts_at' => now()->addSecond()]);
        $this->group('expired', 2, ['expires_at' => now()]);
        $query = app(BannerGroupQuery::class);
        $this->assertSame([$active->getKey()], $query->visibleForPlacement('homepage_main', now())->modelKeys());
        $this->assertNotNull($query->visibleById($active->getKey(), now()));
        $this->assertNull($query->visibleById($active->getKey(), now()->addSecond()));
        $this->assertSame([$next->getKey()], $query->visibleForPlacement('homepage_main', now()->addSecond())->modelKeys());
    }

    public function test_group_can_be_shared_with_promotions_and_deleted_without_deleting_sections(): void
    {
        $group = $this->group('shared');
        $section = ProductCollection::factory()->create()->sections()->create([]);
        $section->bannerGroup()->associate($group)->save();
        $this->assertTrue($section->fresh()->bannerGroup->is($group));
        $this->assertSame([$section->getKey()], $group->promoSections->modelKeys());

        $group->delete();
        $this->assertNull($section->fresh()->banner_group_id);
        $this->assertModelExists($section);
        $this->assertDatabaseCount('banner_cards', 0);
        $this->assertDatabaseCount('banner_group_placements', 0);
    }

    public function test_translation_falls_back_and_membership_cannot_be_mass_assigned(): void
    {
        $group = $this->group('translated');
        $card = $group->cards()->first();
        $card->update(['translations' => ['vi' => ['title' => 'Khám phá', 'alt_text' => '']]]);
        $this->assertSame('Khám phá', $card->localizedText('title', 'vi'));
        $this->assertSame('Explore home', $card->localizedText('title', 'en'));
        $this->assertSame('Explore home', $card->localizedText('title', 'fr'));
        $this->assertSame('A furnished room', $card->localizedText('alt_text', 'vi'));
        $this->assertFalse($card->isFillable('banner_group_id'));
        $this->assertFalse((new PromoSection)->isFillable('banner_group_id'));
        $this->assertFalse((new BannerGroupPlacement)->isFillable('banner_group_id'));
    }

    public function test_unknown_placement_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(BannerGroupQuery::class)->visibleForPlacement('arbitrary-placement');
    }

    public function test_all_layouts_require_their_registered_card_count(): void
    {
        foreach (config('banners.layouts') as $layout => $definition) {
            $this->group($layout, $definition['card_count'], ['layout' => $layout]);
        }
        $this->assertCount(5, app(BannerGroupQuery::class)->visibleForPlacement('homepage_main'));
    }
}
