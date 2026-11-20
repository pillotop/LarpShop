<?php

namespace Database\Factories;

use App\Models\ProductCollection;
use App\Models\PromoSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PromoSection> */
class PromoSectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_collection_id' => ProductCollection::factory(),
            'placement' => 'homepage_main',
            'layout' => 'grid',
            'is_active' => false,
            'sort_order' => 0,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['is_active' => true]);
    }
}
