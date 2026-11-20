<?php

return [
    'placements' => ['homepage_main'],
    'max_sections' => 8,
    'max_managed_sections_per_placement' => 100,
    'collection_page_size' => 24,
    'max_collection_products' => 100,
    'layouts' => [
        'grid' => ['component' => 'promo-layouts.grid', 'product_limit' => 8, 'image_profile' => null],
        'row' => ['component' => 'promo-layouts.row', 'product_limit' => 12, 'image_profile' => null],
        'split' => ['component' => 'promo-layouts.split', 'product_limit' => 4, 'image_profile' => 'portrait'],
    ],
    'image_profiles' => [
        'portrait' => [
            'aspect_ratio' => [4, 5],
            'output_width' => 960,
            'responsive_widths' => [384, 640],
        ],
    ],
    'upload' => [
        'disk' => 'public',
        'max_cropped_bytes' => 8 * 1024 * 1024,
        'webp_quality' => 85,
    ],
];
