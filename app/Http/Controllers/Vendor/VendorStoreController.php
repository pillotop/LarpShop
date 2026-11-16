<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\UpdateStoreDescriptionRequest;
use App\Http\Requests\Vendor\UpdateStoreNameRequest;
use App\Http\Requests\Vendor\UpdateStoreProfileRequest;
use App\Http\Requests\Vendor\UpdateStoreSettingsRequest;
use App\Models\Vendor;
use App\Queries\Vendors\VendorQuery;
use App\Services\Vendors\VendorManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorStoreController extends Controller
{
    public function __construct(
        private VendorManagementService $vendorService,
        private VendorQuery $vendors,
    ) {}

    /**
     * UC-29: Display vendor store settings / configuration page.
     */
    public function edit(Request $request): View
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('active_vendor') ?? $request->user()->activeVendor();

        $vendor->loadCount('products');

        return view('vendor.store.settings', [
            'vendor' => $vendor,
            'ownedVendors' => $request->attributes->get('owned_active_vendors') ?? $this->vendors->activeForUser($request->user()),
            'user' => $request->user(),
        ]);
    }

    /**
     * UC-29: Vendor update store settings / configuration.
     */
    public function update(UpdateStoreSettingsRequest $request): RedirectResponse
    {
        $vendor = $this->vendorService->update(
            $request->user(),
            $request->vendor()->vendor_id,
            $request->validated(),
        );

        $redirect = $request->query('vendor_id') !== null
            ? back()
            : redirect()->route('vendor.store.settings');

        return $redirect
            ->with('success', __('management.messages.store_settings', ['name' => $vendor->store_name]));
    }

    public function updateName(UpdateStoreNameRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->vendorService->updateName($request->user(), $vendor->vendor_id, $request->validated('store_name'));

        return back()->with('success', __('profile.success.store_updated'));
    }

    public function updateProfile(UpdateStoreProfileRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->vendorService->updateProfileImage($request->user(), $vendor->vendor_id, $request->file('brand_image'));

        return back()->with('success', __('profile.success.store_updated'));
    }

    public function updateDescription(UpdateStoreDescriptionRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->vendorService->updateDescription($request->user(), $vendor->vendor_id, $request->validated('description'));

        return back()->with('success', __('profile.success.store_updated'));
    }

    /**
     * Switch the currently managed active store for multi-store owners.
     */
    public function switchStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'vendor_id' => ['required', 'integer', 'min:1'],
        ]);

        $vendor = $this->vendors->findOwned($request->user(), (int) $validated['vendor_id']);

        if (! in_array($vendor->status, ['active', 'approved'], true)) {
            return redirect()->route('profile')
                ->with('warning', __('management.messages.store_active'));
        }

        $request->session()->put('active_vendor_id', $vendor->vendor_id);

        return back()->with('success', __('management.messages.store_switched', ['name' => $vendor->store_name]));
    }
}
