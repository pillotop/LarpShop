<?php

namespace App\Data\Promotions;

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
        return $this->section->heading ?? $this->section->collection->name;
    }
}
