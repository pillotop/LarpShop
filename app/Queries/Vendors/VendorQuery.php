<?php

namespace App\Queries\Vendors;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Collection;

class VendorQuery
{
    public function forUser(User $user): Collection
    {
        return $user->vendors()->withCount('products')->orderBy('vendor_id')->get();
    }

    public function activeForUser(User $user): Collection
    {
        return $user->vendors()->active()->withCount('products')->orderBy('vendor_id')->get();
    }

    public function resolveActiveVendor(User $user, ?int $requestedVendorId = null): ?Vendor
    {
        if ($requestedVendorId !== null && $requestedVendorId > 0) {
            $ownedActive = $user->vendors()->active()->whereKey($requestedVendorId)->first();
            if ($ownedActive) {
                return $ownedActive;
            }
        }

        return $user->activeVendor();
    }

    public function findOwned(User $user, int $vendorId, bool $lockForUpdate = false): Vendor
    {
        $query = $user->vendors()->whereKey($vendorId);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->firstOrFail();
    }
}
