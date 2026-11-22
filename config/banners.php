<?php

return [
    // Card order maps to fixed CSS Grid slots, never database-supplied templates.
    'layouts' => [
        'duo' => ['card_count' => 2, 'sizes' => '(min-width: 576px) 50vw, 100vw'],
        'trio' => ['card_count' => 3, 'sizes' => '(min-width: 896px) 33vw, (min-width: 576px) 50vw, 100vw'],
        'feature' => ['card_count' => 3, 'sizes' => '(min-width: 896px) 67vw, 100vw'],
        'mosaic_4' => ['card_count' => 4, 'sizes' => '(min-width: 896px) 50vw, 100vw'],
        'mosaic_5' => ['card_count' => 5, 'sizes' => '(min-width: 896px) 50vw, 100vw'],
    ],
    'presentations' => ['top_copy', 'bottom_caption'],
    'text_tones' => ['dark', 'light'],
    'placements' => ['homepage_main'],
    'max_groups_per_placement' => 8,
    'cards_upload' => [
        'disk' => 'public',
        'max_bytes' => 8 * 1024 * 1024,
        'max_pixels' => 12_000_000,
        'max_dimension' => 1600,
        'responsive_widths' => [480, 960],
        'quality' => 85,
    ],

    'cache_seconds' => 3600,
    'upload' => [
        'disk' => 'public',
        'max_source_bytes' => 12 * 1024 * 1024,
        'max_source_pixels' => 24_000_000,
        'max_cropped_bytes' => 8 * 1024 * 1024,
        'webp_quality' => 85,
    ],

    'zones' => [
        'vendor_header' => [
            'context' => 'vendor',
            'aspect_ratio' => [4, 1],
            'output_width' => 1600,
            'responsive_widths' => [480, 960],
            'max_visible' => 1,
        ],
    ],
];
