<?php

namespace App\Observers;

use App\Models\Banner;
use App\Services\Banners\BannerCache;
use Illuminate\Support\Facades\DB;

class BannerObserver
{
    public function saved(Banner $banner): void
    {
        $this->invalidate($banner);
    }

    public function deleted(Banner $banner): void
    {
        $this->invalidate($banner);
    }

    private function invalidate(Banner $banner): void
    {
        $vendorIds = array_unique([(int) $banner->vendor_id, (int) $banner->getRawOriginal('vendor_id')]);
        DB::connection($banner->getConnectionName())->afterCommit(function () use ($vendorIds) {
            foreach (array_filter($vendorIds) as $id) {
                app(BannerCache::class)->invalidate($id);
            }
        });
    }
}
