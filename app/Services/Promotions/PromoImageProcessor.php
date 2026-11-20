<?php

namespace App\Services\Promotions;

use App\Data\Images\CroppedImageProfile;
use App\Services\Images\CroppedImageProcessor;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class PromoImageProcessor
{
    public function __construct(private CroppedImageProcessor $images) {}

    public function store(UploadedFile $file, string $profile): array
    {
        $definition = config('promotions.image_profiles')[$profile] ?? null;
        if (! is_array($definition)) {
            throw new InvalidArgumentException('Unsupported promo image profile.');
        }

        return $this->images->store($file, new CroppedImageProfile(
            width: $definition['output_width'],
            ratio: $definition['aspect_ratio'],
            responsiveWidths: $definition['responsive_widths'] ?? [],
            disk: config('promotions.upload.disk'),
            directory: 'promotions',
            maxBytes: config('promotions.upload.max_cropped_bytes'),
            quality: config('promotions.upload.webp_quality'),
        ), 'promo_image', __('promotions.invalid_crop'));
    }
}
