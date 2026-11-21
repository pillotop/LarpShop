<?php

namespace App\Services\Vendors;

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorApplication;
use App\Queries\Vendors\VendorQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class VendorManagementService
{
    public function __construct(private VendorQuery $vendors) {}

    public function update(User $user, int $vendorId, array $data): Vendor
    {
        return DB::transaction(function () use ($user, $vendorId, $data) {
            $vendor = $this->editableVendor($user, $vendorId);
            $vendor->update([
                'store_name' => $data['store_name'],
                'description' => $data['description'] ?? null,
            ]);

            return $vendor;
        });
    }

    public function updateName(User $user, int $vendorId, string $storeName): Vendor
    {
        return DB::transaction(function () use ($user, $vendorId, $storeName) {
            $vendor = $this->editableVendor($user, $vendorId);
            $vendor->update(['store_name' => $storeName]);

            return $vendor;
        });
    }

    public function updateDescription(User $user, int $vendorId, ?string $description): Vendor
    {
        return DB::transaction(function () use ($user, $vendorId, $description) {
            $vendor = $this->editableVendor($user, $vendorId);
            $vendor->update(['description' => $description]);

            return $vendor;
        });
    }

    private function editableVendor(User $user, int $vendorId): Vendor
    {
        $vendor = $this->vendors->findOwned($user, $vendorId, lockForUpdate: true);

        if (! in_array($vendor->status, ['active', 'approved'], true)) {
            throw ValidationException::withMessages([
                'store_name' => __('profile.errors.store_edit'),
            ]);
        }

        return $vendor;
    }

    public function updateProfileImage(User $user, int $vendorId, UploadedFile $brandImage): Vendor
    {
        $newPath = null;
        $previousPath = null;

        try {
            $vendor = DB::transaction(function () use ($user, $vendorId, $brandImage, &$newPath, &$previousPath) {
                $vendor = $this->editableVendor($user, $vendorId);

                $previousPath = $vendor->brand_path;
                $newPath = $brandImage->store('vendors/profiles', 'public');
                $vendor->brand_path = $newPath;

                $vendor->save();

                return $vendor;
            });
        } catch (Throwable $exception) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }

            throw $exception;
        }

        if ($newPath && $previousPath
            && ! VendorApplication::query()->where('brand_path', $previousPath)->exists()
            && ! Vendor::query()->where('brand_path', $previousPath)->exists()) {
            Storage::disk('public')->delete($previousPath);
        }

        return $vendor;
    }
}
