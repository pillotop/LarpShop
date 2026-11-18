<?php

namespace App\Queries\Banners;

use App\Models\Banner;
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
        $restored = $this->restoreCachedItems($cached, $vendor, $zone, $now);
        if ($restored !== null) {
            return $restored;
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
        Cache::put($key, [
            // Raw attributes retain JSON/date strings for Eloquent casts on hydration.
            'items' => $items->map(fn (Banner $banner) => $banner->getAttributes())->all(),
            'expires_at' => $expiry->getTimestamp(),
        ], $expiry);

        return $items;
    }

    private function restoreCachedItems(mixed $cached, Vendor $vendor, string $zone, CarbonInterface $at): ?Collection
    {
        if (! is_array($cached) || ! is_int($cached['expires_at'] ?? null)
            || $cached['expires_at'] <= $at->getTimestamp()
            || ! is_array($cached['items'] ?? null) || ! array_is_list($cached['items'])) {
            return null;
        }

        foreach ($cached['items'] as $attributes) {
            if (! is_array($attributes)
                || ! isset($attributes['banner_id'], $attributes['vendor_id'])
                || (int) $attributes['vendor_id'] !== (int) $vendor->vendor_id
                || ($attributes['zone'] ?? null) !== $zone) {
                return null;
            }
            foreach ($attributes as $value) {
                if ($value !== null && ! is_scalar($value)) {
                    return null;
                }
            }
        }

        // Hydration restores persisted models and casts without a database query.
        return $vendor->banners()->getRelated()->newQuery()->hydrate($cached['items']);
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
