<?php

return [
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
