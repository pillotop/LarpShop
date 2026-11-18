<?php

namespace Tests\Feature\Vendor;

use App\Models\Banner;
use App\Models\User;
use App\Queries\Banners\BannerQuery;
use App\Services\Banners\BannerCache;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class VendorBannerDatabaseCacheTest extends VendorBannerCacheTest
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'database', 'cache.serializable_classes' => false]);
        Cache::purge('database');
    }

    public function test_database_cache_restores_models_and_casts_without_unserializing_classes(): void
    {
        $vendor = User::factory()->create()->vendors()->create(['store_name' => 'Database Cache Store', 'status' => 'active']);
        $banner = $vendor->banners()->create([
            'zone' => 'vendor_header', 'title' => 'Cached collection', 'alt_text' => 'Armor',
            'image_disk' => 'public', 'image_path' => 'banners/full.webp',
            'image_width' => 1600, 'image_height' => 400, 'is_active' => true,
            'starts_at' => now()->subMinute(),
            'image_variants' => [['path' => 'banners/small.webp', 'width' => 480, 'height' => 120]],
        ]);
        $query = app(BannerQuery::class);
        $first = $query->visibleForVendor($vendor, 'vendor_header');
        $cached = $query->visibleForVendor($vendor, 'vendor_header');

        $this->assertInstanceOf(Collection::class, $cached);
        $this->assertSame($first->modelKeys(), $cached->modelKeys());
        $restored = $cached->sole();
        $this->assertInstanceOf(Banner::class, $restored);
        $this->assertTrue($restored->exists);
        $this->assertFalse($restored->isDirty());
        $this->assertTrue($restored->is_active);
        $this->assertSame($banner->starts_at->timestamp, $restored->starts_at->timestamp);
        $this->assertSame(480, $restored->image_variants[0]['width']);

        $key = app(BannerCache::class)->key($vendor->vendor_id, 'vendor_header', 1);
        $payload = Cache::get($key);
        $this->assertSame($payload, unserialize(serialize($payload), ['allowed_classes' => false]));
        $this->assertIsArray($payload['items']);
        $this->assertIsArray($payload['items'][0]);
        $this->assertSame($banner->fresh()->getAttributes(), $payload['items'][0]);
    }

    public function test_legacy_object_entries_are_ignored_and_malformed_entries_are_rebuilt(): void
    {
        $vendor = User::factory()->create()->vendors()->create(['store_name' => 'Legacy Cache Store', 'status' => 'active']);
        $banner = $vendor->banners()->create([
            'zone' => 'vendor_header', 'title' => 'Fresh data', 'alt_text' => 'Armor',
            'image_disk' => 'public', 'image_path' => 'banners/example.webp',
            'image_width' => 1600, 'image_height' => 400, 'is_active' => true,
        ]);
        $key = app(BannerCache::class)->key($vendor->vendor_id, 'vendor_header', 1);
        $legacyKey = str_replace('banners:v2:vendor:', 'banners:vendor:', $key);
        Cache::put($legacyKey, ['items' => new Collection([$banner]), 'expires_at' => now()->addHour()->timestamp], 3600);
        $this->assertNotSame($legacyKey, $key);
        $query = app(BannerQuery::class);
        $this->assertSame([$banner->banner_id], $query->visibleForVendor($vendor, 'vendor_header')->modelKeys());

        foreach ([
            ['items' => new Collection([$banner]), 'expires_at' => now()->addHour()->timestamp],
            ['items' => 'invalid', 'expires_at' => now()->addHour()->timestamp],
            ['items' => [$banner->getAttributes()], 'expires_at' => 'invalid'],
            ['items' => [['vendor_id' => 999, 'banner_id' => 999, 'zone' => 'vendor_header']], 'expires_at' => now()->addHour()->timestamp],
        ] as $invalid) {
            Cache::put($key, $invalid, 3600);
            $this->assertSame([$banner->banner_id], $query->visibleForVendor($vendor, 'vendor_header')->modelKeys());
            $this->assertIsArray(Cache::get($key)['items'][0]);
        }
    }

    public function test_guest_storefront_renders_on_cold_and_warm_database_cache(): void
    {
        $this->withoutVite();
        $vendor = User::factory()->create()->vendors()->create(['store_name' => 'Public Cache Store', 'status' => 'active']);
        $vendor->banners()->create([
            'zone' => 'vendor_header', 'title' => 'Collection', 'alt_text' => 'Cached public image',
            'image_disk' => 'public', 'image_path' => 'banners/public.webp',
            'image_width' => 1600, 'image_height' => 400, 'is_active' => true,
            'image_variants' => [['path' => 'banners/public-480.webp', 'width' => 480, 'height' => 120]],
        ]);
        for ($request = 0; $request < 2; $request++) {
            $this->get(route('vendors.show', $vendor->vendor_id))->assertOk()
                ->assertSee('Cached public image')
                ->assertSee('banners/public-480.webp 480w', false);
        }
    }
}
