<?php

namespace App\Observers;

use App\Models\Vendor;
use App\Services\Banners\BannerCache;
use Illuminate\Support\Facades\DB;

class VendorBannerObserver
{
    public function updated(Vendor $vendor): void
    {
        if ($vendor->isDirty('status')) {
            $id = (int) $vendor->vendor_id;
            DB::connection($vendor->getConnectionName())->afterCommit(fn () => app(BannerCache::class)->invalidate($id));
        }
    }
}
