<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminUpdateVendorRequest;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminVendorController extends Controller
{
    /**
     * UC-40: List vendors and applications with status filtering.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');

        $query = Vendor::with('user')->latest();

        if ($status !== 'all' && in_array($status, ['pending', 'approved', 'active', 'rejected', 'suspended'])) {
            if ($status === 'approved') {
                $query->whereIn('status', ['approved', 'active']);
            } else {
                $query->where('status', $status);
            }
        }

        $vendors = $query->paginate(12)->withQueryString();

        $counts = [
            'all' => Vendor::count(),
            'pending' => Vendor::where('status', 'pending')->count(),
            'approved' => Vendor::whereIn('status', ['approved', 'active'])->count(),
            'rejected' => Vendor::where('status', 'rejected')->count(),
            'suspended' => Vendor::where('status', 'suspended')->count(),
        ];

        return view('admin.vendors.index', compact('vendors', 'status', 'counts'));
    }

    /**
     * UC-40: Approve a pending vendor application.
     */
    public function approve(Vendor $vendor): RedirectResponse
    {
        $vendor->status = 'approved';
        $vendor->save();

        return back()->with('success', __('management.messages.store_approved', ['name' => $vendor->store_name]));
    }

    /**
     * UC-40: Reject a pending vendor application.
     */
    public function reject(Request $request, Vendor $vendor): RedirectResponse
    {
        $vendor->status = 'rejected';
        $vendor->save();

        return back()->with('warning', __('management.messages.store_rejected', ['name' => $vendor->store_name]));
    }

    /**
     * UC-40: Suspend an active vendor store.
     */
    public function suspend(Vendor $vendor): RedirectResponse
    {
        $vendor->status = 'suspended';
        $vendor->save();

        return back()->with('warning', __('management.messages.store_suspended', ['name' => $vendor->store_name]));
    }

    /**
     * UC-40: Reinstate/re-activate a suspended or rejected vendor store.
     */
    public function reinstate(Vendor $vendor): RedirectResponse
    {
        $vendor->status = 'approved';
        $vendor->save();

        return back()->with('success', __('management.messages.store_reinstated', ['name' => $vendor->store_name]));
    }

    /**
     * UC-29: Administrator edit/configure vendor store.
     */
    public function edit(Vendor $vendor): View
    {
        $vendor->load('user', 'products');

        return view('admin.vendors.edit', compact('vendor'));
    }

    /**
     * UC-29: Administrator update vendor store configuration.
     */
    public function update(AdminUpdateVendorRequest $request, Vendor $vendor): RedirectResponse
    {
        $vendor->update($request->validated());

        return redirect()->route('admin.vendors.index')
            ->with('success', __('management.messages.store_configuration', ['name' => $vendor->store_name]));
    }
}
