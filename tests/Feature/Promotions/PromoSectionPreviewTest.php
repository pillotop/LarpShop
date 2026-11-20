<?php

namespace Tests\Feature\Promotions;

use App\Models\ProductCollection;
use App\Models\User;
use App\Queries\Promotions\PromoSectionQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class PromoSectionPreviewTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function draft(): ProductCollection
    {
        $collection = ProductCollection::factory()->create(['name' => 'Holiday essentials']);
        $collection->products()->attach($this->product('Preview sword'));

        return $collection;
    }

    public function test_only_admins_can_access_the_preview(): void
    {
        $url = route('admin.promotions.preview');
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get($url)
            ->assertOk()->assertSee('No collections available');
    }

    #[DataProvider('layouts')]
    public function test_admin_can_preview_each_layout_without_publishing_or_saving(string $layout): void
    {
        $collection = $this->draft();
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.promotions.preview', ['collection' => $collection->getKey(), 'layout' => $layout]))
            ->assertOk()->assertSee('Holiday essentials')->assertSee('Preview sword')
            ->assertSee('data-promo-layout="'.$layout.'"', false)->assertSee('inert', false);
        $this->assertFalse($collection->fresh()->is_published);
        $this->assertDatabaseCount('promo_sections', 0);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public static function layouts(): array
    {
        return [['grid'], ['row'], ['split']];
    }

    public function test_preview_of_an_existing_scheduled_section_can_override_layout_without_writing(): void
    {
        $collection = $this->draft();
        $section = $collection->sections()->create([
            'layout' => 'split', 'heading' => 'Seasonal picks', 'is_active' => false,
            'starts_at' => now()->addMonth(), 'image_path' => 'promotions/portrait.webp',
            'image_width' => 960, 'image_height' => 1200,
            'alt_text' => 'Seasonal artwork',
            'image_variants' => [['path' => 'promotions/portrait-384.webp', 'width' => 384, 'height' => 480]],
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.promotions.preview', ['section' => $section->getKey()]))
            ->assertOk()->assertSee('Seasonal picks')->assertSee('Seasonal artwork')
            ->assertSee('promotions/portrait-384.webp')->assertSee('srcset=', false);
        $this->get(route('admin.promotions.preview', ['section' => $section->getKey(), 'layout' => 'row']))
            ->assertOk()->assertSee('data-promo-layout="row"', false);
        $this->assertSame('split', $section->fresh()->layout);
        $this->assertFalse($section->fresh()->is_active);
        $this->assertCount(0, app(PromoSectionQuery::class)->visibleForPlacement('homepage_main'));
    }

    public function test_preview_validation_rejects_unknown_layout_and_invalid_identifiers(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->getJson(route('admin.promotions.preview', [
            'layout' => '../../layouts.admin', 'collection' => 999999, 'section' => ['bad'],
        ]))->assertUnprocessable()->assertJsonValidationErrors(['layout', 'collection', 'section']);
    }

    public function test_preview_uses_localized_interface_and_escapes_authored_content(): void
    {
        $collection = $this->draft();
        $collection->update(['name' => '<script>alert(1)</script>']);
        $this->actingAs(User::factory()->create(['role' => 'admin']))->withSession(['locale' => 'vi'])
            ->get(route('admin.promotions.preview', ['collection' => $collection->getKey()]))
            ->assertOk()->assertSee('Xem trước khu quảng bá')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_empty_or_ineligible_collections_have_an_explicit_preview_state(): void
    {
        $collection = $this->draft();
        $collection->products->first()->update(['status' => 'rejected']);
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.promotions.preview', ['collection' => $collection->getKey()]))
            ->assertOk()->assertSee('no eligible products in stock')
            ->assertDontSee('data-promo-layout=', false);
    }

    public function test_repeated_sections_have_unique_headings_and_scoped_scroll_controls(): void
    {
        $data = app(PromoSectionQuery::class)->preview($this->draft(), 'row');
        $html = Blade::render('<x-promo-section :data="$data" /><x-promo-section :data="$data" />', compact('data'));
        preg_match_all('/aria-labelledby="(promo-heading-[^"]+)"/', $html, $matches);
        $this->assertCount(2, array_unique($matches[1]));
        $this->assertSame(2, substr_count($html, 'data-scroll-content'));
        $this->assertSame(2, substr_count($html, 'data-scroll-previous'));
        $this->assertStringNotContainsString('id="arrow-left"', $html);
        $this->assertStringNotContainsString('id="two-arrow-scrollable"', $html);
    }

    public function test_renderer_omits_unknown_layouts_and_only_shows_a_supplied_collection_link(): void
    {
        $data = app(PromoSectionQuery::class)->preview($this->draft(), 'grid');
        $html = Blade::render('<x-promo-section :data="$data" view-all-url="/collections/holiday" />', compact('data'));
        $this->assertStringContainsString('href="/collections/holiday"', $html);
        $data->section->layout = '../../layouts.admin';
        $html = Blade::render('<x-promo-section :data="$data" />', compact('data'));
        $this->assertStringNotContainsString('data-promo-layout', $html);
    }
}
