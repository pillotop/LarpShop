<?php

namespace Tests\Feature\Vendor;

use App\Models\User;
use App\Models\Vendor;
use App\Queries\Banners\BannerQuery;
use App\Services\Banners\BannerCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VendorBannerCacheTest extends TestCase
{
    use RefreshDatabase;

    private function vendor(string $name = 'Cache Store'): Vendor
    {
        return User::factory()->create()->vendors()->create(['store_name' => $name, 'status' => 'active']);
    }

    private function banner(Vendor $vendor, array $data = [])
    {
        return $vendor->banners()->create(array_merge([
            'zone' => 'vendor_header', 'title' => 'Collection', 'alt_text' => 'Armor',
            'image_disk' => 'public', 'image_path' => 'banners/cache.webp',
            'image_width' => 1600, 'image_height' => 400, 'is_active' => true,
        ], $data));
    }

    public function test_warm_reads_reuse_cached_results_without_banner_queries(): void
    {
        Cache::flush();
        $vendor = $this->vendor();
        $banner = $this->banner($vendor);
        $query = app(BannerQuery::class);
        $query->visibleForVendor($vendor, 'vendor_header');
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->assertSame([$banner->banner_id], $query->visibleForVendor($vendor, 'vendor_header')->modelKeys());
        $this->assertSame([], DB::getQueryLog());
        DB::disableQueryLog();
    }

    public function test_empty_cache_expires_at_start_and_visible_cache_expires_at_end(): void
    {
        $this->travelTo(now()->startOfDay());
        $vendor = $this->vendor();
        $start = now()->addMinutes(10);
        $end = now()->addMinutes(20);
        $banner = $this->banner($vendor, ['starts_at' => $start, 'expires_at' => $end]);
        $query = app(BannerQuery::class);
        $this->assertTrue($query->visibleForVendor($vendor, 'vendor_header')->isEmpty());
        $this->travelTo($start);
        $this->assertSame([$banner->banner_id], $query->visibleForVendor($vendor, 'vendor_header')->modelKeys());
        $this->travelTo($end);
        $this->assertTrue($query->visibleForVendor($vendor, 'vendor_header')->isEmpty());
    }

    public function test_mutations_invalidate_cache_including_previous_owner_and_zone(): void
    {
        $vendor = $this->vendor();
        $other = $this->vendor('Other Cache Store');
        $banner = $this->banner($vendor);
        $query = app(BannerQuery::class);
        $query->visibleForVendor($vendor, 'vendor_header');
        $this->assertTrue($query->visibleForVendor($other, 'vendor_header')->isEmpty());
        $banner->vendor_id = $other->vendor_id;
        $banner->save();
        $this->assertTrue($query->visibleForVendor($vendor, 'vendor_header')->isEmpty());
        $this->assertCount(1, $query->visibleForVendor($other, 'vendor_header'));
        $banner->update(['zone' => 'future_zone']);
        $this->assertTrue($query->visibleForVendor($other, 'vendor_header')->isEmpty());
        $banner->update(['zone' => 'vendor_header']);
        $this->assertCount(1, $query->visibleForVendor($other, 'vendor_header'));
        $banner->delete();
        $this->assertTrue($query->visibleForVendor($other, 'vendor_header')->isEmpty());
    }

    public function test_vendor_status_change_invalidates_visibility(): void
    {
        $vendor = $this->vendor();
        $this->banner($vendor);
        $query = app(BannerQuery::class);
        $this->assertCount(1, $query->visibleForVendor($vendor, 'vendor_header'));
        $vendor->update(['status' => 'suspended']);
        $this->assertTrue($query->visibleForVendor($vendor, 'vendor_header')->isEmpty());
        $vendor->update(['status' => 'active']);
        $this->assertCount(1, $query->visibleForVendor($vendor, 'vendor_header'));
    }

    public function test_rollback_does_not_rotate_live_namespace(): void
    {
        $vendor = $this->vendor();
        $banner = $this->banner($vendor);
        $cache = app(BannerCache::class);
        $key = $cache->key($vendor->vendor_id, 'vendor_header', 1);
        DB::beginTransaction();
        $banner->update(['title' => 'Uncommitted']);
        $this->assertSame($key, $cache->key($vendor->vendor_id, 'vendor_header', 1));
        DB::rollBack();
        $this->assertSame($key, $cache->key($vendor->vendor_id, 'vendor_header', 1));
        $this->assertSame('Collection', $banner->fresh()->title);
    }

    public function test_explicit_time_read_does_not_pollute_live_cache(): void
    {
        $vendor = $this->vendor();
        $this->banner($vendor, ['starts_at' => now()->addDay()]);
        $query = app(BannerQuery::class);
        $this->assertCount(1, $query->visibleForVendor($vendor, 'vendor_header', now()->addDays(2)));
        $this->assertTrue($query->visibleForVendor($vendor, 'vendor_header')->isEmpty());
    }
}
