<?php

namespace App\Queries\Banners;

use App\Models\Vendor;
use App\Services\Banners\BannerCache;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class BannerQuery
{
    public function __construct(private BannerCache $cache) {}

    public function managedByVendor(Vendor $vendor): Collection
    {
        return $vendor->banners()->orderBy('zone')->orderBy('sort_order')->orderBy('banner_id')->get();
    }

    public function visibleForVendor(Vendor $vendor, string $zone, ?CarbonInterface $at = null): Collection
    {
        $definition = config('banners.zones')[$zone] ?? null;

        if (! is_array($definition) || ($definition['context'] ?? null) !== 'vendor') {
            throw new InvalidArgumentException('Unsupported vendor banner zone.');
        }

        // Explicit-time reads are deterministic and never share the live cache.
        if ($at !== null) {
            return $this->visibleAt($vendor, $zone, $definition['max_visible'], $at);
        }

        $now = CarbonImmutable::now();
        $key = $this->cache->key((int) $vendor->vendor_id, $zone, $definition['max_visible']);
        $cached = Cache::get($key);
        if ($cached && $cached['expires_at'] > $now->getTimestamp()) {
            return $cached['items'];
        }

        $boundary = $vendor->banners()->where('zone', $zone)->active()
            ->selectRaw('MIN(CASE WHEN starts_at > ? THEN starts_at END) AS next_start', [$now])
            ->selectRaw('MIN(CASE WHEN expires_at > ? THEN expires_at END) AS next_end', [$now])
            ->first();
        $expiry = $now->addSeconds((int) config('banners.cache_seconds', 3600));
        foreach ([$boundary?->next_start, $boundary?->next_end] as $date) {
            if ($date !== null) {
                $expiry = $expiry->min(CarbonImmutable::parse($date, config('app.timezone')));
            }
        }

        $items = $this->visibleAt($vendor, $zone, $definition['max_visible'], $now);
        Cache::put($key, ['items' => $items, 'expires_at' => $expiry->getTimestamp()], $expiry);

        return $items;
    }

    private function visibleAt(Vendor $vendor, string $zone, int $limit, CarbonInterface $at): Collection
    {
        return $vendor->banners()
            ->where('zone', $zone)
            ->whereHas('vendor', fn (Builder $query) => $query->active())
            ->active()
            ->scheduledAt($at)
            ->orderBy('sort_order')
            ->orderBy('banner_id')
            ->limit($limit)
            ->get();
    }
}
