<?php

namespace App\Services\Banners;

use App\Data\Images\CroppedImageProfile;
use App\Services\Images\CroppedImageProcessor;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class BannerImageProcessor
{
    public function __construct(private CroppedImageProcessor $images) {}

    public function store(UploadedFile $file, string $zone): array
    {
        $definition = config('banners.zones')[$zone] ?? null;
        if (! is_array($definition)) {
            throw new InvalidArgumentException('Unsupported banner zone.');
        }

        return $this->images->store($file, new CroppedImageProfile(
            width: $definition['output_width'],
            ratio: $definition['aspect_ratio'],
            responsiveWidths: $definition['responsive_widths'] ?? [],
            disk: config('banners.upload.disk'),
            directory: 'banners',
            maxBytes: config('banners.upload.max_cropped_bytes'),
            quality: config('banners.upload.webp_quality'),
        ), 'banner_image', __('banners.invalid_crop'));
    }
}
