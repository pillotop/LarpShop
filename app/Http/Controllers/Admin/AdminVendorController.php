<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminUpdateVendorRequest;
use App\Http\Requests\Admin\RejectVendorApplicationRequest;
use App\Models\Vendor;
use App\Services\Vendors\Onboarding\VendorApplicationReviewService;
use App\Support\Countries\CountryCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminVendorController extends Controller
{
    public function __construct(private VendorApplicationReviewService $reviews) {}

    /**
     * UC-40: List vendors and applications with status filtering.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');

        $query = Vendor::with('user', 'application')->latest();

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
    public function approve(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->reviews->decide($request->user(), $vendor->getKey(), approve: true);

        return back()->with('success', __('management.messages.store_approved', ['name' => $vendor->store_name]));
    }

    /**
     * UC-40: Reject a pending vendor application.
     */
    public function reject(RejectVendorApplicationRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->reviews->decide($request->user(), $vendor->getKey(), approve: false, reason: $request->validated('rejection_reason'));

        return back()->with('warning', __('management.messages.store_rejected', ['name' => $vendor->store_name]));
    }

    /**
     * UC-40: Suspend an active vendor store.
     */
    public function suspend(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->reviews->changeAvailability($request->user(), $vendor->getKey(), suspend: true);

        return back()->with('warning', __('management.messages.store_suspended', ['name' => $vendor->store_name]));
    }

    /**
     * UC-40: Reinstate/re-activate a suspended vendor store.
     */
    public function reinstate(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->reviews->changeAvailability($request->user(), $vendor->getKey(), suspend: false);

        return back()->with('success', __('management.messages.store_reinstated', ['name' => $vendor->store_name]));
    }

    public function show(Vendor $vendor, CountryCatalog $countries): View
    {
        $vendor->load('user', 'application.destinations', 'application.reviewer');

        return view('admin.vendors.show', [
            'vendor' => $vendor,
            'application' => $vendor->application,
            'countries' => $countries->all(),
        ]);
    }

    /**
     * UC-29: Administrator edit/configure vendor store.
     */
    public function edit(Vendor $vendor): View|RedirectResponse
    {
        if (in_array($vendor->status, ['pending', 'rejected'], true)) {
            return redirect()->route('admin.vendors.show', $vendor);
        }
        $vendor->load('user', 'products');

        return view('admin.vendors.edit', compact('vendor'));
    }

    /**
     * UC-29: Administrator update vendor store configuration.
     */
    public function update(AdminUpdateVendorRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->reviews->configure($request->user(), $vendor->getKey(), $request->validated());
        $vendor->refresh();

        return redirect()->route('admin.vendors.index')
            ->with('success', __('management.messages.store_configuration', ['name' => $vendor->store_name]));
    }
}
