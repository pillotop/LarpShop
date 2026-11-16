<?php

namespace Tests\Feature\Vendor;

use App\Models\Banner;
use App\Models\User;
use App\Models\Vendor;
use App\Queries\Banners\BannerQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class VendorBannerQueryTest extends TestCase
{
    use RefreshDatabase;

    private function vendor(string $name = 'Banner Store'): Vendor
    {
        return User::factory()->create()->vendors()->create([
            'store_name' => $name,
            'status' => 'approved',
        ]);
    }

    private function banner(Vendor $vendor, array $attributes = []): Banner
    {
        return $vendor->banners()->create(array_merge([
            'zone' => 'vendor_header',
            'title' => 'Summer collection',
            'alt_text' => 'Handcrafted armor and accessories',
            'image_path' => 'banners/example.webp',
            'image_width' => 1600,
            'image_height' => 400,
            'is_active' => true,
        ], $attributes));
    }

    public function test_banners_are_isolated_by_vendor_and_zone(): void
    {
        $vendor = $this->vendor();
        $other = $this->vendor('Other Store');
        $this->banner($other);
        $this->banner($vendor, ['zone' => 'homepage_hero']);
        $expected = $this->banner($vendor);

        $result = app(BannerQuery::class)->visibleForVendor($vendor, 'vendor_header');

        $this->assertSame([$expected->banner_id], $result->modelKeys());
        $this->assertTrue($expected->vendor->is($vendor));
    }

    public function test_schedule_uses_exact_time_and_excludes_disabled_banners(): void
    {
        $this->travelTo(now()->startOfDay()->addHours(12));
        $vendor = $this->vendor();
        $this->banner($vendor, ['is_active' => false]);
        $this->banner($vendor, ['starts_at' => now()->addSecond()]);
        $this->banner($vendor, ['expires_at' => now()]);
        $this->banner($vendor, ['expires_at' => now()->subSecond()]);
        $expected = $this->banner($vendor, ['starts_at' => now(), 'expires_at' => now()->addSecond()]);

        $query = app(BannerQuery::class);
        $this->assertSame([$expected->banner_id], $query->visibleForVendor($vendor, 'vendor_header')->modelKeys());
        $this->assertTrue($query->visibleForVendor($vendor, 'vendor_header', now()->subDay())->isNotEmpty());
    }

    public function test_open_ended_schedules_and_deterministic_order_respect_zone_limit(): void
    {
        $vendor = $this->vendor();
        $this->banner($vendor, ['sort_order' => 10]);
        $first = $this->banner($vendor, ['sort_order' => 2]);
        $second = $this->banner($vendor, ['sort_order' => 2]);
        $query = app(BannerQuery::class);

        $this->assertSame([$first->banner_id], $query->visibleForVendor($vendor, 'vendor_header')->modelKeys());
        config(['banners.zones.vendor_header.max_visible' => 2]);
        $this->assertSame([$first->banner_id, $second->banner_id], $query->visibleForVendor($vendor, 'vendor_header')->modelKeys());
    }

    public function test_unpublished_vendor_has_no_visible_banners(): void
    {
        $vendor = $this->vendor();
        $this->banner($vendor);
        foreach (['pending', 'rejected', 'suspended'] as $status) {
            $vendor->update(['status' => $status]);
            $this->assertTrue(app(BannerQuery::class)->visibleForVendor($vendor, 'vendor_header')->isEmpty());
        }
    }

    public function test_unknown_zone_is_rejected(): void
    {
        $vendor = $this->vendor();
        $this->expectException(InvalidArgumentException::class);
        app(BannerQuery::class)->visibleForVendor($vendor, 'unknown');
    }

    public function test_new_banners_are_inactive_by_default(): void
    {
        $vendor = $this->vendor();
        $banner = $vendor->banners()->create([
            'zone' => 'vendor_header', 'title' => 'Draft', 'alt_text' => 'Draft image',
            'image_path' => 'banners/draft.webp', 'image_width' => 1600, 'image_height' => 400,
        ])->fresh();

        $this->assertFalse($banner->is_active);
        $this->assertTrue(app(BannerQuery::class)->visibleForVendor($vendor, 'vendor_header')->isEmpty());
    }
}
