<?php

namespace App\Queries\Banners;

use App\Models\Vendor;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class BannerQuery
{
    public function visibleForVendor(Vendor $vendor, string $zone, ?CarbonInterface $at = null): Collection
    {
        $definition = config('banners.zones')[$zone] ?? null;

        if (! is_array($definition) || ($definition['context'] ?? null) !== 'vendor') {
            throw new InvalidArgumentException('Unsupported vendor banner zone.');
        }

        return $vendor->banners()
            ->where('zone', $zone)
            ->whereHas('vendor', fn (Builder $query) => $query->active())
            ->active()
            ->scheduledAt($at ?? now())
            ->orderBy('sort_order')
            ->orderBy('banner_id')
            ->limit($definition['max_visible'])
            ->get();
    }
}
