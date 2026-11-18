<?php

namespace App\Services\Banners;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BannerCache
{
    public function key(int $vendorId, string $zone, int $limit): string
    {
        $versionKey = $this->versionKey($vendorId);
        Cache::add($versionKey, (string) Str::uuid(), now()->addDays(7));

        // v2 stores raw attribute arrays instead of serialized Eloquent objects.
        return 'banners:v2:vendor:'.$vendorId.':'.Cache::get($versionKey).':'.$zone.':'.$limit;
    }

    public function invalidate(int $vendorId): void
    {
        // Rotate the namespace so a concurrent old read cannot repopulate the live key.
        Cache::put($this->versionKey($vendorId), (string) Str::uuid(), now()->addDays(7));
    }

    private function versionKey(int $vendorId): string
    {
        return 'banners:vendor:'.$vendorId.':version';
    }
}
