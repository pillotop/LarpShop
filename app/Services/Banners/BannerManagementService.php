<?php

namespace App\Services\Banners;

use App\Models\Banner;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class BannerManagementService
{
    public function __construct(private BannerImageProcessor $images) {}

    public function save(User $user, Vendor $vendor, array $data, ?UploadedFile $image, ?Banner $banner = null): Banner
    {
        $stored = null;
        $previous = null;

        try {
            $saved = DB::transaction(function () use ($user, $vendor, $data, $image, $banner, &$stored, &$previous) {
                $lockedVendor = $this->ownedVendor($user, $vendor);
                $record = $banner
                    ? $lockedVendor->banners()->lockForUpdate()->findOrFail($banner->banner_id)
                    : new Banner;

                if ($image) {
                    $stored = $this->images->store($image, $data['zone']);
                    if ($record->exists) {
                        $previous = ['image_disk' => $record->image_disk, 'image_path' => $record->image_path, 'image_variants' => $record->image_variants];
                    }
                }

                // Explicit fields prevent ownership and image metadata injection.
                $record->fill(collect($data)->only([
                    'title', 'alt_text', 'zone', 'link_url', 'starts_at', 'expires_at', 'is_active', 'sort_order',
                ])->all());
                if ($stored) {
                    $record->fill($stored);
                }
                $lockedVendor->banners()->save($record);

                if ($previous) {
                    DB::afterCommit(fn () => $this->removeImage($previous));
                }

                return $record;
            });
        } catch (Throwable $exception) {
            if ($stored) {
                $this->removeImage($stored);
            }
            throw $exception;
        }

        return $saved;
    }

    public function setActive(User $user, Vendor $vendor, Banner $banner, bool $active): void
    {
        DB::transaction(function () use ($user, $vendor, $banner, $active) {
            $record = $this->ownedVendor($user, $vendor)->banners()->lockForUpdate()->findOrFail($banner->banner_id);
            $record->update(['is_active' => $active]);
        });
    }

    public function delete(User $user, Vendor $vendor, Banner $banner): void
    {
        DB::transaction(function () use ($user, $vendor, $banner) {
            $record = $this->ownedVendor($user, $vendor)->banners()->lockForUpdate()->findOrFail($banner->banner_id);
            $image = ['image_disk' => $record->image_disk, 'image_path' => $record->image_path, 'image_variants' => $record->image_variants];
            $record->delete();
            DB::afterCommit(fn () => $this->removeImage($image));
        });
    }

    private function ownedVendor(User $user, Vendor $vendor): Vendor
    {
        return $user->vendors()->active()->lockForUpdate()->findOrFail($vendor->vendor_id);
    }

    private function removeImage(array $image): void
    {
        // Cleanup failure must not turn a committed banner update into a failed request.
        try {
            if (! Storage::disk($image['image_disk'])->delete([
                $image['image_path'],
                ...array_column($image['image_variants'] ?? [], 'path'),
            ])) {
                report(new \RuntimeException('Unable to remove an unused banner image.'));
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
