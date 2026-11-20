<?php

namespace App\Services\Promotions;

use App\Models\ProductCollection;
use App\Models\PromoSection;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class PromoSectionManagementService
{
    public function __construct(private PromoImageProcessor $images) {}

    public function save(array $data, ?UploadedFile $image, ?PromoSection $section = null): PromoSection
    {
        $stored = null;
        try {
            return DB::transaction(function () use ($data, $image, $section, &$stored) {
                $collection = ProductCollection::query()->lockForUpdate()->findOrFail($data['product_collection_id']);
                $records = $this->lockedSections([$data['placement'], $section?->placement ?? $data['placement']]);
                $record = $section ? $records->find($section->getKey()) : new PromoSection;
                abort_if($record === null, 404);
                $siblings = $records->where('placement', $data['placement']);
                if (! $record->exists && $siblings->count() >= config('promotions.max_managed_sections_per_placement')) {
                    throw ValidationException::withMessages(['placement' => __('promotions.section_limit')]);
                }

                $previous = $this->imageMetadata($record);
                $profile = config('promotions.layouts')[$data['layout']]['image_profile'];
                if ($image) {
                    $stored = $this->images->store($image, $profile);
                }
                $record->fill(Arr::only($data, ['heading', 'introduction', 'placement', 'layout', 'is_active', 'alt_text']));
                foreach (['starts_at', 'expires_at'] as $field) {
                    $record->{$field} = empty($data[$field]) ? null : CarbonImmutable::parse($data[$field], config('app.timezone'));
                }
                if (! $record->exists || $record->placement !== $record->getOriginal('placement')) {
                    $record->sort_order = (int) $siblings->max('sort_order') + 1;
                }
                if ($data['remove_image'] ?? false) {
                    $record->fill(['image_path' => null, 'image_width' => null, 'image_height' => null, 'image_variants' => null]);
                }
                if ($stored) {
                    $record->fill($stored);
                }
                $this->assertRenderableImage($record);
                $collection->sections()->save($record);

                if ($previous && ($stored || ($data['remove_image'] ?? false))) {
                    DB::afterCommit(fn () => $this->removeImage($previous));
                }

                return $record;
            });
        } catch (Throwable $exception) {
            if ($stored) {
                $this->removeImage($stored);
            }
            throw $exception;
        }
    }

    public function setActive(PromoSection $section, bool $active): void
    {
        DB::transaction(function () use ($section, $active) {
            $record = $this->lockedSections([$section->placement])->find($section->getKey());
            abort_if($record === null, 404);
            if ($active) {
                $this->assertRenderableImage($record);
            }
            $record->update(['is_active' => $active]);
        });
    }

    public function move(PromoSection $section, string $direction): void
    {
        DB::transaction(function () use ($section, $direction) {
            $records = $this->lockedSections([$section->placement])->values();
            $position = $records->search(fn (PromoSection $record) => $record->is($section));
            abort_if($position === false, 404);
            $neighbor = $position + ($direction === 'up' ? -1 : 1);
            if (! isset($records[$neighbor])) {
                return;
            }
            $ids = $records->modelKeys();
            [$ids[$position], $ids[$neighbor]] = [$ids[$neighbor], $ids[$position]];
            foreach ($ids as $order => $id) {
                $records->find($id)->update(['sort_order' => $order]);
            }
        });
    }

    public function delete(PromoSection $section): void
    {
        DB::transaction(function () use ($section) {
            $record = $this->lockedSections([$section->placement])->find($section->getKey());
            abort_if($record === null, 404);
            $image = $this->imageMetadata($record);
            $record->delete();
            if ($image) {
                DB::afterCommit(fn () => $this->removeImage($image));
            }
        });
    }

    private function lockedSections(array $placements): Collection
    {
        return PromoSection::query()->whereIn('placement', array_unique($placements))
            ->orderBy('placement')->orderBy('sort_order')->orderBy('promo_section_id')->lockForUpdate()->get();
    }

    private function assertRenderableImage(PromoSection $record): void
    {
        $layout = config('promotions.layouts')[$record->layout] ?? null;
        if (! $layout) {
            throw ValidationException::withMessages(['layout' => __('promotions.invalid_layout')]);
        }
        if ($layout['image_profile'] !== null && (! $record->image_path || ! trim($record->alt_text ?? ''))) {
            throw ValidationException::withMessages(['promo_image' => __('promotions.image_required')]);
        }
    }

    private function imageMetadata(PromoSection $record): ?array
    {
        return $record->image_path ? Arr::only($record->getAttributes(), ['image_disk', 'image_path'])
            + ['image_variants' => $record->image_variants] : null;
    }

    private function removeImage(array $image): void
    {
        try {
            if (! Storage::disk($image['image_disk'])->delete([
                $image['image_path'], ...array_column($image['image_variants'] ?? [], 'path'),
            ])) {
                report(new \RuntimeException('Unable to remove an unused promo image.'));
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
