<?php

namespace App\Services\Promotions;

use App\Models\BannerGroupPlacement;
use App\Models\HomepageSystemBlock;
use App\Models\PromoSection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HomepageLayoutService
{
    private const SYSTEM_BLOCKS = ['broad_select', 'category_discovery'];

    public function sort(Collection $blocks): Collection
    {
        return $blocks->sortBy(fn ($block) => [
            $block['position'] === null ? 1 : 0,
            $block['position'] ?? 0,
            match ($block['type']) {
                'banner' => 0, 'promo' => 1, 'system' => 2
            },
            $block['legacy_order'], $block['id'],
        ])->values();
    }

    public function managedBlocks(bool $lock = false): Collection
    {
        $banners = BannerGroupPlacement::where('placement', 'homepage_main')->with('group')
            ->when($lock, fn ($query) => $query->lockForUpdate())->orderBy('banner_group_placement_id')->get();
        $promos = PromoSection::where('placement', 'homepage_main')->with('collection')
            ->when($lock, fn ($query) => $query->lockForUpdate())->orderBy('promo_section_id')->get();

        return $this->sort($banners->map(fn ($record) => [
            'key' => 'banner:'.$record->getKey(), 'type' => 'banner', 'id' => $record->getKey(),
            'position' => $record->homepage_position, 'legacy_order' => $record->sort_order,
            'label' => $record->group->name, 'enabled' => $record->group->is_active,
            'edit_url' => route('admin.banner-groups.edit', $record->group),
        ])->toBase()->concat($promos->map(fn ($record) => [
            'key' => 'promo:'.$record->getKey(), 'type' => 'promo', 'id' => $record->getKey(),
            'position' => $record->homepage_position, 'legacy_order' => $record->sort_order,
            'label' => $record->displayHeading(), 'enabled' => $record->is_active,
            'edit_url' => route('admin.sections.edit', $record),
        ]))->concat($this->systemBlocks($lock)));
    }

    public function systemBlocks(bool $lock = false): Collection
    {
        if ($lock) {
            foreach (self::SYSTEM_BLOCKS as $key) {
                HomepageSystemBlock::firstOrCreate(['block_key' => $key]);
            }
        }
        $records = HomepageSystemBlock::whereIn('block_key', self::SYSTEM_BLOCKS)
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->orderBy('homepage_system_block_id')->get()->keyBy('block_key');

        // Missing configuration must never remove built-in sections or make a GET write data.
        return collect(self::SYSTEM_BLOCKS)->map(function ($key, $index) use ($records) {
            $record = $records->get($key);

            return [
                'key' => 'system:'.$key, 'type' => 'system', 'id' => $record?->getKey() ?? $index + 1,
                'system_kind' => $key, 'position' => $record?->homepage_position,
                'legacy_order' => $index, 'label' => __('homepage_layout.'.$key),
                'enabled' => true, 'edit_url' => null,
            ];
        });
    }

    public function revision(Collection $blocks): string
    {
        return hash('sha256', $blocks->map(fn ($block) => [
            $block['key'], $block['position'], $block['legacy_order'],
        ])->toJson());
    }

    public function save(array $positions, string $revision): void
    {
        DB::transaction(function () use ($positions, $revision) {
            $blocks = $this->managedBlocks(lock: true);
            $expected = $blocks->pluck('key')->sort()->values()->all();
            $submitted = collect(array_keys($positions))->sort()->values()->all();
            if ($expected !== $submitted || ! hash_equals($this->revision($blocks), $revision)) {
                throw ValidationException::withMessages(['positions' => __('homepage_layout.stale')]);
            }
            $ordered = $blocks->sortBy(fn ($block) => (int) $positions[$block['key']])->values();
            foreach ($ordered as $position => $block) {
                if ($block['type'] === 'system') {
                    HomepageSystemBlock::whereKey($block['id'])->update(['homepage_position' => $position]);
                } else {
                    $model = $block['type'] === 'banner' ? BannerGroupPlacement::class : PromoSection::class;
                    $model::whereKey($block['id'])->where('placement', 'homepage_main')
                        ->update(['homepage_position' => $position]);
                }
            }
        });
    }
}
