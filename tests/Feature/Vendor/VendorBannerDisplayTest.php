<?php

namespace Tests\Feature\Vendor;

use App\Models\Banner;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorBannerDisplayTest extends TestCase
{
    use RefreshDatabase;

    private function vendor(string $name = 'Banner Display Store'): Vendor
    {
        return User::factory()->create()->vendors()->create(['store_name' => $name, 'status' => 'active']);
    }

    private function banner(Vendor $vendor, array $data = []): Banner
    {
        return $vendor->banners()->create(array_merge([
            'zone' => 'vendor_header', 'title' => 'Collection', 'alt_text' => 'Public banner',
            'image_path' => 'banners/public.webp', 'image_width' => 1600, 'image_height' => 400,
            'is_active' => true,
        ], $data));
    }

    public function test_guest_sees_linked_header_banner_with_reserved_dimensions_and_high_priority(): void
    {
        $vendor = $this->vendor();
        $this->banner($vendor, ['link_url' => 'https://example.com/collection']);
        $this->get(route('vendors.show', $vendor->vendor_id))->assertOk()
            ->assertSee('data-banner-zone="vendor_header"', false)
            ->assertSee('href="https://example.com/collection"', false)
            ->assertSee('alt="Public banner"', false)
            ->assertSee('width="1600"', false)
            ->assertSee('height="400"', false)
            ->assertSee('fetchpriority="high"', false)
            ->assertSee('loading="eager"', false)
            ->assertDontSee('data-bs-slide="next"', false);
    }

    public function test_no_visible_banner_leaves_no_zone_wrapper(): void
    {
        $vendor = $this->vendor();
        $this->banner($vendor, ['is_active' => false]);
        $this->banner($vendor, ['starts_at' => now()->addHour()]);
        $this->banner($vendor, ['expires_at' => now()->subHour()]);
        $other = $this->vendor('Other Display Store');
        $this->banner($other);

        $this->get(route('vendors.show', $vendor->vendor_id))->assertOk()
            ->assertDontSee('data-banner-zone=', false);
    }

    public function test_priority_selects_one_banner_without_leaking_other_store_images(): void
    {
        $vendor = $this->vendor();
        $this->banner($vendor, ['image_path' => 'banners/later.webp', 'sort_order' => 10]);
        $this->banner($vendor, ['image_path' => 'banners/first.webp', 'sort_order' => 1]);
        $this->banner($this->vendor('Another'), ['image_path' => 'banners/other.webp']);

        $this->get(route('vendors.show', $vendor->vendor_id))->assertOk()
            ->assertSee('banners/first.webp', false)
            ->assertDontSee('banners/later.webp', false)
            ->assertDontSee('banners/other.webp', false);
    }

    public function test_component_escapes_alt_text_and_omits_unsafe_links(): void
    {
        $banner = new Banner([
            'zone' => 'vendor_header', 'alt_text' => '"><script>alert(1)</script>',
            'image_disk' => 'public', 'image_path' => 'banners/example.webp',
            'image_width' => 1600, 'image_height' => 400, 'link_url' => 'javascript:alert(1)',
        ]);
        $this->blade('<x-banner-zone zone="vendor_header" :banners="$banners" />', ['banners' => [$banner]])
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('href=', false)
            ->assertSee('&lt;script&gt;', false)
            ->assertSee('loading="lazy"', false);
    }

    public function test_component_emits_responsive_sources_and_legacy_images_still_render(): void
    {
        $banner = new Banner([
            'image_disk' => 'public', 'image_path' => 'banners/full.webp', 'alt_text' => 'Responsive image',
            'image_width' => 1600, 'image_height' => 400,
            'image_variants' => [['path' => 'banners/small.webp', 'width' => 480, 'height' => 120]],
        ]);
        $this->blade('<x-banner-zone zone="vendor_header" :banners="$banners" sizes="50vw" />', ['banners' => [$banner]])
            ->assertSee('banners/small.webp 480w', false)
            ->assertSee('banners/full.webp 1600w', false)
            ->assertSee('sizes="50vw"', false);
        $banner->image_variants = null;
        $this->blade('<x-banner-zone zone="vendor_header" :banners="$banners" />', ['banners' => [$banner]])
            ->assertSee('banners/full.webp', false)
            ->assertDontSee('srcset=', false);
    }

    public function test_multiple_banners_use_bootstrap_controls_with_unique_targets(): void
    {
        $vendor = $this->vendor();
        $first = $this->banner($vendor);
        $second = $this->banner($vendor, ['image_path' => 'banners/second.webp']);
        $html = (string) $this->blade(
            '<x-banner-zone zone="vendor_header" :banners="$banners" /><x-banner-zone zone="vendor_header" :banners="$banners" />',
            ['banners' => [$first, $second]],
        );
        preg_match_all('/id="(banner-zone-[^"]+)"/', $html, $matches);
        $this->assertCount(2, array_unique($matches[1]));
        foreach ($matches[1] as $id) {
            $this->assertStringContainsString('data-bs-target="#'.$id.'"', $html);
        }
        $this->assertStringContainsString('data-bs-slide="next"', $html);
        $this->assertStringNotContainsString('data-bs-autoplay', $html);
    }
}
