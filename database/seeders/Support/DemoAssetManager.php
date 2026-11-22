<?php

namespace Database\Seeders\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Publishes tracked local demo product images to the public storage disk without hotlinking.
 */
class DemoAssetManager
{
    private const SOURCE_SUBDIR = 'database/seeders/assets/products';

    private const TARGET_SUBDIR = 'demo-products';

    private bool $synced = false;

    /**
     * Ensure all local product images exist on the public storage disk.
     */
    public function syncToPublicDisk(): void
    {
        if ($this->synced) {
            return;
        }

        $sourceDir = base_path(self::SOURCE_SUBDIR);
        if (! File::isDirectory($sourceDir)) {
            $this->synced = true;

            return;
        }

        $disk = Storage::disk('public');

        foreach (File::files($sourceDir) as $file) {
            $relativePath = self::TARGET_SUBDIR.'/'.$file->getFilename();
            if (! $disk->exists($relativePath) || $disk->size($relativePath) !== $file->getSize()) {
                $disk->put($relativePath, File::get($file->getPathname()));
            }
        }

        $this->synced = true;
    }

    /**
     * Resolve a filename in assets/products/ to its public-disk relative path, falling back if needed.
     */
    public function publicPath(string $filename): string
    {
        $this->syncToPublicDisk();

        $candidate = self::TARGET_SUBDIR.'/'.$filename;
        if (Storage::disk('public')->exists($candidate)) {
            return $candidate;
        }

        $existing = Storage::disk('public')->files(self::TARGET_SUBDIR);

        return $existing[0] ?? 'products/payload.svg';
    }
}
