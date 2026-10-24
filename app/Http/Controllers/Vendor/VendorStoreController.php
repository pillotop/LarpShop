<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\UpdateStoreSettingsRequest;
use App\Models\Vendor;
use App\Services\Vendors\VendorManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorStoreController extends Controller
{
    public function __construct(
        private VendorManagementService $vendorService,
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
            'user' => $request->user(),
        ]);
    }

    /**
     * UC-29: Vendor update store settings / configuration.
     */
    public function update(UpdateStoreSettingsRequest $request): RedirectResponse
    {
        $vendor = $request->vendor();

        $vendor->update($request->validated());

        return redirect()->route('vendor.store.settings')
            ->with('success', 'Thông tin gian hàng đã được cập nhật thành công.');
    }
}
