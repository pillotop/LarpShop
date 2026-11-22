<?php

namespace App\Services\Banners;

use App\Models\BannerCard;
use App\Models\BannerGroup;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class BannerGroupManagementService
{
    public function __construct(private BannerCardImageProcessor $images) {}

    public function save(array $data, ?BannerGroup $group = null): BannerGroup
    {
        $stored = [];
        try {
            return DB::transaction(function () use ($data, $group, &$stored) {
                $record = $group ? BannerGroup::query()->lockForUpdate()->findOrFail($group->getKey()) : new BannerGroup;
                $existing = $record->exists ? $record->cards()->lockForUpdate()->get()->keyBy('banner_card_id') : collect();
                $record->fill(Arr::only($data, ['name', 'slug', 'layout', 'is_active', 'starts_at', 'expires_at']));
                $record->save();
                $kept = [];
                $retired = [];
                foreach ($data['cards'] as $index => $values) {
                    $card = ! empty($values['id']) ? $existing->get((int) $values['id']) : new BannerCard;
                    if (! $card) {
                        throw ValidationException::withMessages(["cards.$index.id" => __('banner_groups.invalid_card')]);
                    }
                    $previous = $card->exists ? $this->metadata($card) : null;
                    $card->fill(Arr::only($values, [
                        ...BannerCard::TEXT_FIELDS, 'translations', 'link_url', 'presentation', 'text_tone',
                        'focal_x', 'focal_y', 'is_active', 'sort_order',
                    ]));
                    if (! empty($values['image'])) {
                        $image = $this->images->store($values['image'], "cards.$index.image");
                        $stored[] = $image;
                        $card->fill($image);
                        if ($previous) {
                            $retired[] = $previous;
                        }
                    }
                    if (! $card->image_path) {
                        throw ValidationException::withMessages(["cards.$index.image" => __('banner_groups.image_required')]);
                    }
                    $record->cards()->save($card);
                    $kept[] = $card->getKey();
                }
                foreach ($existing->except($kept) as $card) {
                    $retired[] = $this->metadata($card);
                    $card->delete();
                }
                $placements = $data['placements'] ?? [];
                $record->placements()->whereNotIn('placement', $placements)->delete();
                foreach ($placements as $placement) {
                    $assignment = $record->placements()->firstOrNew(['placement' => $placement]);
                    $assignment->sort_order = $data['placement_orders'][$placement] ?? $assignment->sort_order ?? 0;
                    $assignment->save();
                }
                DB::afterCommit(function () use ($retired) {
                    foreach ($retired as $image) {
                        $this->removeUnusedImage($image);
                    }
                });

                return $record;
            });
        } catch (Throwable $exception) {
            foreach ($stored as $image) {
                $this->removeUnusedImage($image);
            }
            throw $exception;
        }
    }

    public function delete(BannerGroup $group): void
    {
        DB::transaction(function () use ($group) {
            $record = BannerGroup::query()->lockForUpdate()->findOrFail($group->getKey());
            $images = $record->cards()->get()->map(fn ($card) => $this->metadata($card))->all();
            $record->delete();
            DB::afterCommit(function () use ($images) {
                foreach ($images as $image) {
                    $this->removeUnusedImage($image);
                }
            });
        });
    }

    private function metadata(BannerCard $card): array
    {
        return Arr::only($card->getAttributes(), ['image_disk', 'image_path'])
            + ['image_variants' => $card->image_variants ?? []];
    }

    private function removeUnusedImage(array $image): void
    {
        // Imported repository assets are shared and must never be deleted by this service.
        if ($image['image_disk'] !== config('banners.cards_upload.disk')
            || ! str_starts_with($image['image_path'], 'banner-groups/')) {
            return;
        }
        try {
            if (BannerCard::where('image_disk', $image['image_disk'])->where('image_path', $image['image_path'])->exists()) {
                return;
            }
            $paths = array_filter([$image['image_path'], ...array_column($image['image_variants'], 'path')],
                fn ($path) => is_string($path) && preg_match('/\Abanner-groups\/[a-f0-9-]+(?:-\d+)?\.webp\z/', $path));
            if (! Storage::disk($image['image_disk'])->delete($paths)) {
                report(new \RuntimeException('Unable to remove unused banner group images.'));
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
