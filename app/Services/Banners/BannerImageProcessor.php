<?php

namespace App\Services\Banners;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class BannerImageProcessor
{
    /**
     * Process a browser-cropped image. The caller owns authorization and DB/file lifecycle.
     *
     * @return array{image_disk: string, image_path: string, image_width: int, image_height: int, image_variants: array}
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

        $disk = config('banners.upload.disk');
        $base = 'banners/'.Str::uuid();
        $written = [];
        try {
            imagepalettetotruecolor($image);
            imagesavealpha($image, true);
            $path = $base.'.webp';
            $written[] = $path;
            $this->encode($image, $disk, $path);
            $variants = [];
            foreach (array_unique($definition['responsive_widths'] ?? []) as $targetWidth) {
                if ($targetWidth <= 0 || $targetWidth >= $width) {
                    continue;
                }
                $targetHeight = max(1, (int) round($targetWidth * $height / $width));
                $resized = imagescale($image, $targetWidth, $targetHeight, IMG_BICUBIC_FIXED);
                if ($resized === false) {
                    throw new RuntimeException('Unable to resize banner image.');
                }
                try {
                    imagesavealpha($resized, true);
                    $variantPath = $base.'-'.$targetWidth.'.webp';
                    $written[] = $variantPath;
                    $this->encode($resized, $disk, $variantPath);
                    $variants[] = ['path' => $variantPath, 'width' => $targetWidth, 'height' => $targetHeight];
                } finally {
                    imagedestroy($resized);
                }
            }

            return [
                'image_disk' => $disk,
                'image_path' => $path,
                'image_width' => $width,
                'image_height' => $height,
                'image_variants' => $variants,
            ];
        } catch (Throwable $exception) {
            try {
                Storage::disk($disk)->delete($written);
            } catch (Throwable $cleanup) {
                report($cleanup);
            }
            throw $exception;
        } finally {
            imagedestroy($image);
        }
    }

    private function encode(GdImage $image, string $disk, string $path): void
    {
        $stream = fopen('php://temp', 'w+b');
        try {
            if (! imagewebp($image, $stream, config('banners.upload.webp_quality'))) {
                throw new RuntimeException('Unable to encode banner image.');
            }
            rewind($stream);
            if (fstat($stream)['size'] === 0 || ! Storage::disk($disk)->put($path, $stream)) {
                throw new RuntimeException('Unable to store banner image.');
            }
        } finally {
            fclose($stream);
        }
    }
}
