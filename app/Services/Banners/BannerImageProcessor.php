<?php

namespace App\Services\Banners;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

class BannerImageProcessor
{
    /**
     * Process a browser-cropped image. The caller owns authorization and DB/file lifecycle.
     *
     * @return array{image_disk: string, image_path: string, image_width: int, image_height: int}
     */
    public function store(UploadedFile $file, string $zone): array
    {
        $definition = config('banners.zones')[$zone] ?? null;
        if (! is_array($definition)) {
            throw new InvalidArgumentException('Unsupported banner zone.');
        }

        $width = $definition['output_width'];
        [$ratioWidth, $ratioHeight] = $definition['aspect_ratio'];
        $height = (int) round($width * $ratioHeight / $ratioWidth);
        $size = $file->isValid() ? @getimagesize($file->getPathname()) : false;

        // Fixed output dimensions bound decoding memory and enforce the crop contract.
        if (! $size || $file->getSize() > config('banners.upload.max_cropped_bytes')
            || ! in_array($size['mime'], ['image/png', 'image/webp'], true)
            || $size[0] !== $width || $size[1] !== $height) {
            throw ValidationException::withMessages(['banner_image' => __('banners.invalid_crop')]);
        }

        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            throw new RuntimeException('Banner processing requires PHP GD with WebP support.');
        }

        $image = @imagecreatefromstring(file_get_contents($file->getPathname()));
        if ($image === false) {
            throw ValidationException::withMessages(['banner_image' => __('banners.invalid_crop')]);
        }

        $stream = fopen('php://temp', 'w+b');
        try {
            imagepalettetotruecolor($image);
            imagesavealpha($image, true);
            if (! imagewebp($image, $stream, config('banners.upload.webp_quality'))) {
                throw new RuntimeException('Unable to encode banner image.');
            }
            rewind($stream);
            if (fstat($stream)['size'] === 0) {
                throw new RuntimeException('Banner encoder returned an empty image.');
            }

            $disk = config('banners.upload.disk');
            $path = 'banners/'.Str::uuid().'.webp';
            if (! Storage::disk($disk)->put($path, $stream)) {
                throw new RuntimeException('Unable to store banner image.');
            }

            return [
                'image_disk' => $disk,
                'image_path' => $path,
                'image_width' => $width,
                'image_height' => $height,
            ];
        } finally {
            fclose($stream);
            imagedestroy($image);
        }
    }
}
