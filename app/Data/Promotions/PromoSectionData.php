<?php

namespace App\Data\Promotions;

use App\Enums\PromoSource;
use App\Models\PromoSection;
use Illuminate\Database\Eloquent\Collection;

final readonly class PromoSectionData
{
    public function __construct(
        public PromoSection $section,
        public Collection $products,
    ) {}

    public function heading(): string
    {
        return $this->section->displayHeading();
    }

    public function introduction(): ?string
    {
        return $this->section->introduction ?? $this->section->collection?->description;
    }

    public function viewAllUrl(): ?string
    {
        $collection = $this->section->collection;

        return $this->section->source === PromoSource::Collection->value && $collection?->is_published
            ? route('collections.show', ['slug' => $collection->slug])
            : null;
    }
}
