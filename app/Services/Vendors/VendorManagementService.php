<?php

namespace App\Services\Vendors;

use App\Models\User;
use App\Models\Vendor;
use App\Queries\Vendors\VendorQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VendorManagementService
{
    public function __construct(private VendorQuery $vendors) {}

    public function register(User $user, array $data): Vendor
    {
        return $user->vendors()->create([
            'store_name' => $data['store_name'],
            'description' => $data['description'] ?? null,
            'status' => 'pending',
        ]);
    }

    public function update(User $user, int $vendorId, array $data): Vendor
    {
        return DB::transaction(function () use ($user, $vendorId, $data) {
            $vendor = $this->vendors->findOwned($user, $vendorId, lockForUpdate: true);

            if (! in_array($vendor->status, ['active', 'approved'], true)) {
                throw ValidationException::withMessages([
                    'store_name' => 'Only an active or approved store can be edited.',
                ]);
            }

            $vendor->update([
                'store_name' => $data['store_name'],
                'description' => $data['description'] ?? null,
            ]);

            return $vendor;
        });
    }

    public function resubmit(User $user, int $vendorId, array $data): Vendor
    {
        return DB::transaction(function () use ($user, $vendorId, $data) {
            $vendor = $this->vendors->findOwned($user, $vendorId, lockForUpdate: true);

            if ($vendor->status !== 'rejected') {
                throw ValidationException::withMessages([
                    'store_name' => 'Only a rejected application can be resubmitted.',
                ]);
            }

            $vendor->update([
                'store_name' => $data['store_name'],
                'description' => $data['description'] ?? null,
                'status' => 'pending',
            ]);

            return $vendor;
        });
    }
}
