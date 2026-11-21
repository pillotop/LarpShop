<?php

namespace App\Queries\Vendors;

use App\Models\User;
use App\Models\VendorApplication;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VendorApplicationQuery
{
    public function editableForUser(User $user): LengthAwarePaginator
    {
        return $user->vendorApplications()->whereIn('status', ['draft', 'rejected'])
            ->latest('vendor_application_id')->paginate(6, ['*'], 'applications_page');
    }

    public function forUser(User $user): LengthAwarePaginator
    {
        return $user->vendorApplications()->with('destinations')
            ->latest('vendor_application_id')->paginate(15);
    }

    public function findOwned(User $user, int $id, bool $lock = false): VendorApplication
    {
        return $user->vendorApplications()->when($lock, fn ($query) => $query->lockForUpdate())
            ->findOrFail($id);
    }
}
