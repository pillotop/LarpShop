<?php

namespace App\Queries\Banners;

use App\Models\BannerGroup;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class BannerGroupQuery
{
    public function visibleForPlacement(string $placement, ?CarbonInterface $at = null): Collection
    {
        if (! in_array($placement, config('banners.placements'), true)) {
            throw new InvalidArgumentException('Unsupported banner group placement.');
        }

        return $this->visible($at)
            ->join('banner_group_placements as placements', 'placements.banner_group_id', '=', 'banner_groups.banner_group_id')
            ->where('placements.placement', $placement)
            ->orderByRaw('placements.homepage_position IS NULL')->orderBy('placements.homepage_position')
            ->orderBy('placements.sort_order')->orderBy('placements.banner_group_placement_id')
            ->limit((int) config('banners.max_groups_per_placement'))
            ->get(['banner_groups.*', 'placements.homepage_position', 'placements.sort_order as placement_order', 'placements.banner_group_placement_id as placement_id']);
    }

    public function visibleById(int $groupId, ?CarbonInterface $at = null): ?BannerGroup
    {
        return $this->visible($at)->find($groupId);
    }

    public function visibleByIds(array $ids, ?CarbonInterface $at = null): Collection
    {
        if ($ids === []) {
            return new Collection;
        }

        return $this->visible($at)->whereIn('banner_group_id', array_unique($ids))->get()->keyBy('banner_group_id');
    }

    public function recentChoices(?int $selectedId = null): Collection
    {
        $choices = BannerGroup::query()->orderByDesc('banner_group_id')->limit(50)->get();
        if ($selectedId && ! $choices->contains($selectedId)) {
            if ($selected = BannerGroup::find($selectedId)) {
                $choices->push($selected);
            }
        }

        return $choices;
    }

    private function visible(?CarbonInterface $at): Builder
    {
        return BannerGroup::query()->visibleAt($at ?? CarbonImmutable::now())
            ->where(function (Builder $query) {
                foreach (config('banners.layouts') as $layout => $definition) {
                    $query->orWhere(fn (Builder $query) => $query->where('layout', $layout)
                        ->whereHas('cards', fn (Builder $cards) => $cards->where('is_active', true), '=', $definition['card_count']));
                }
            })
            ->with(['cards' => fn ($cards) => $cards->where('is_active', true)]);
    }
}
