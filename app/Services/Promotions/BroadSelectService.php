<?php

namespace App\Services\Promotions;

use App\Data\Promotions\BroadSelectGroupData;
use App\Data\Promotions\PromoSectionData;
use App\Queries\Promotions\PromoSectionQuery;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class BroadSelectService
{
    public function __construct(private PromoSectionQuery $sections) {}

    /** @return Collection<int, BroadSelectGroupData> */
    public function forHomepage(?CarbonInterface $at = null): Collection
    {
        return $this->sections->visibleForPlacement(config('promotions.broad_select.placement'), $at)
            ->map(fn (PromoSectionData $data) => new BroadSelectGroupData(
                key: 'promo-'.$data->section->getKey(),
                label: $data->heading(),
                source: $data->section->source,
                products: $data->products,
                viewAllUrl: $data->viewAllUrl(),
            ));
    }
}
