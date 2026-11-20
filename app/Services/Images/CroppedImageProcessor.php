<?php

namespace App\Services\Images;

use App\Data\Images\CroppedImageProfile;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class CroppedImageProcessor
{
    /**
     * Process a browser-cropped image. The caller owns authorization and DB/file lifecycle.
     *
     * @return array{image_disk: string, image_path: string, image_width: int, image_height: int, image_variants: array}
     */
    public function store(
        UploadedFile $file,
        CroppedImageProfile $profile,
        string $errorField,
        string $invalidCropMessage,
    ): array {
        $width = $profile->width;
        [$ratioWidth, $ratioHeight] = $profile->ratio;
        $height = (int) round($width * $ratioHeight / $ratioWidth);
        $size = $file->isValid() ? @getimagesize($file->getPathname()) : false;

        // Fixed output dimensions bound decoding memory and enforce the crop contract.
        if (! $size || $file->getSize() > $profile->maxBytes
            || ! in_array($size['mime'], ['image/png', 'image/webp'], true)
            || $size[0] !== $width || $size[1] !== $height) {
            throw ValidationException::withMessages([$errorField => $invalidCropMessage]);
        }

        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            throw new RuntimeException('Image processing requires PHP GD with WebP support.');
        }

        $image = @imagecreatefromstring(file_get_contents($file->getPathname()));
        if ($image === false) {
            throw ValidationException::withMessages([$errorField => $invalidCropMessage]);
        }

        $disk = $profile->disk;
        $base = $profile->directory.'/'.Str::uuid();
        $written = [];
        try {
            imagepalettetotruecolor($image);
            imagesavealpha($image, true);
            $path = $base.'.webp';
            $written[] = $path;
            $this->encode($image, $disk, $path, $profile->quality);
            $variants = [];
            foreach (array_unique($profile->responsiveWidths) as $targetWidth) {
                if ($targetWidth <= 0 || $targetWidth >= $width) {
                    continue;
                }
                $targetHeight = max(1, (int) round($targetWidth * $height / $width));
                $resized = imagescale($image, $targetWidth, $targetHeight, IMG_BICUBIC_FIXED);
                if ($resized === false) {
                    throw new RuntimeException('Unable to resize image.');
                }
                try {
                    imagesavealpha($resized, true);
                    $variantPath = $base.'-'.$targetWidth.'.webp';
                    $written[] = $variantPath;
                    $this->encode($resized, $disk, $variantPath, $profile->quality);
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

    private function encode(GdImage $image, string $disk, string $path, int $quality): void
    {
        $stream = fopen('php://temp', 'w+b');
        try {
            if (! imagewebp($image, $stream, $quality)) {
                throw new RuntimeException('Unable to encode image.');
            }
            rewind($stream);
            if (fstat($stream)['size'] === 0 || ! Storage::disk($disk)->put($path, $stream)) {
                throw new RuntimeException('Unable to store image.');
            }
        } finally {
            fclose($stream);
        }
    }
}
