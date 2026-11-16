<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Banners\ManageVendorBannerRequest;
use App\Http\Requests\Banners\SaveVendorBannerRequest;
use App\Http\Requests\Banners\ToggleVendorBannerRequest;
use App\Models\Banner;
use App\Models\Vendor;
use App\Services\Banners\BannerManagementService;
use Illuminate\Http\RedirectResponse;

class VendorBannerController extends Controller
{
    public function __construct(private BannerManagementService $banners) {}

    public function store(SaveVendorBannerRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->banners->save($request->user(), $vendor, $request->safe()->except('banner_image'), $request->file('banner_image'));

        return $this->backToStore($vendor);
    }

    public function update(SaveVendorBannerRequest $request, Vendor $vendor, Banner $banner): RedirectResponse
    {
        $this->banners->save($request->user(), $vendor, $request->safe()->except('banner_image'), $request->file('banner_image'), $banner);

        return $this->backToStore($vendor);
    }

    public function toggle(ToggleVendorBannerRequest $request, Vendor $vendor, Banner $banner): RedirectResponse
    {
        $this->banners->setActive($request->user(), $vendor, $banner, $request->boolean('is_active'));

        return $this->backToStore($vendor);
    }

    public function destroy(ManageVendorBannerRequest $request, Vendor $vendor, Banner $banner): RedirectResponse
    {
        $this->banners->delete($request->user(), $vendor, $banner);

        return $this->backToStore($vendor);
    }

    private function backToStore(Vendor $vendor): RedirectResponse
    {
        return redirect()->route('vendors.show', ['vendor' => $vendor->vendor_id])
            ->with('banner_settings_open', true)
            ->with('success', __('banners.saved'));
    }
}
