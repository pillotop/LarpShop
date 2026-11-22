<?php

namespace App\Services\Banners;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class BannerCardImageProcessor
{
    public function store(UploadedFile $file, string $errorField): array
    {
        $definition = config('banners.cards_upload');
        $size = $file->isValid() ? @getimagesize($file->getPathname()) : false;
        if (! $size || $file->getSize() > $definition['max_bytes']
            || ! in_array($size['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)
            || min($size[0], $size[1]) < 320 || max($size[0], $size[1]) > 8192
            || $definition['max_pixels'] < $size[0] * $size[1]) {
            throw ValidationException::withMessages([$errorField => __('banner_groups.invalid_image')]);
        }
        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            throw new RuntimeException('Banner uploads require PHP GD with WebP support.');
        }
        $source = @imagecreatefromstring(file_get_contents($file->getPathname()));
        if (! $source) {
            throw ValidationException::withMessages([$errorField => __('banner_groups.invalid_image')]);
        }

        // Match browser previews of camera photos and strip metadata in the WebP output.
        if ($size['mime'] === 'image/jpeg' && function_exists('exif_read_data')) {
            $orientation = (int) (@exif_read_data($file->getPathname())['Orientation'] ?? 1);
            if (in_array($orientation, [2, 4, 5, 7], true)) {
                imageflip($source, IMG_FLIP_HORIZONTAL);
            }
            $angle = match ($orientation) {
                3, 4 => 180, 5, 8 => 90, 6, 7 => -90, default => 0,
            };
            if ($angle !== 0) {
                $rotated = imagerotate($source, $angle, 0);
                if (! $rotated) {
                    imagedestroy($source);
                    throw new RuntimeException('Unable to orient banner image.');
                }
                imagedestroy($source);
                $source = $rotated;
            }
            $size[0] = imagesx($source);
            $size[1] = imagesy($source);
        }

        $disk = $definition['disk'];
        $base = 'banner-groups/'.Str::uuid();
        $written = [];
        $variants = [];
        // Bound both dimensions while retaining the source composition for focal cropping.
        $scale = min(1, $definition['max_dimension'] / max($size[0], $size[1]));
        $width = max(1, (int) round($size[0] * $scale));
        $height = max(1, (int) round($size[1] * $scale));
        try {
            imagepalettetotruecolor($source);
            $widths = array_unique([$width, ...array_filter($definition['responsive_widths'], fn ($value) => $value < $width)]);
            foreach ($widths as $targetWidth) {
                $targetHeight = max(1, (int) round($targetWidth * $height / $width));
                $target = imagecreatetruecolor($targetWidth, $targetHeight);
                imagealphablending($target, false);
                imagesavealpha($target, true);
                try {
                    if (! imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $size[0], $size[1])) {
                        throw new RuntimeException('Unable to resize banner image.');
                    }
                    $path = $targetWidth === $width ? $base.'.webp' : $base.'-'.$targetWidth.'.webp';
                    $written[] = $path;
                    $stream = fopen('php://temp', 'w+b');
                    try {
                        if (! imagewebp($target, $stream, $definition['quality'])) {
                            throw new RuntimeException('Unable to encode banner image.');
                        }
                        rewind($stream);
                        if (fstat($stream)['size'] === 0 || ! Storage::disk($disk)->put($path, $stream)) {
                            throw new RuntimeException('Unable to store banner image.');
                        }
                    } finally {
                        fclose($stream);
                    }
                    if ($targetWidth !== $width) {
                        $variants[] = ['path' => $path, 'width' => $targetWidth, 'height' => $targetHeight];
                    }
                } finally {
                    imagedestroy($target);
                }
            }

            return ['image_disk' => $disk, 'image_path' => $base.'.webp', 'image_width' => $width,
                'image_height' => $height, 'image_variants' => $variants];
        } catch (Throwable $exception) {
            try {
                Storage::disk($disk)->delete($written);
            } catch (Throwable $cleanup) {
                report($cleanup);
            }
            throw $exception;
        } finally {
            imagedestroy($source);
        }
    }
}
