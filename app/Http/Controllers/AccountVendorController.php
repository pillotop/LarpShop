<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateVendorRequest;
use App\Models\Vendor;
use App\Services\Vendors\Onboarding\VendorOnboardingService;
use App\Services\Vendors\VendorManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccountVendorController extends Controller
{
    public function __construct(private VendorManagementService $vendors) {}

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('onboarding.review.use_onboarding'),
                'redirect_url' => route('vendor-applications.create'),
            ], 422);
        }

        return redirect()->route('vendor-applications.create', status: 303);
    }

    public function update(UpdateVendorRequest $request): RedirectResponse|JsonResponse
    {
        $vendor = $this->vendors->update($request->user(), $request->vendor()->vendor_id, $request->validated());

        return $this->respond($request, $vendor, 'vendor-updated', __('profile.success.store_updated'));
    }

    public function resubmit(Request $request, int $vendor, VendorOnboardingService $onboarding): RedirectResponse|JsonResponse
    {
        $application = $onboarding->resumeRejectedStore($request->user(), $vendor);
        $url = route('vendor-applications.show', $application);
        if ($request->expectsJson()) {
            return response()->json(['application' => $application, 'redirect_url' => $url]);
        }

        return redirect()->to($url, 303);
    }

    private function respond(Request $request, Vendor $vendor, string $status, string $message, int $code = 200): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'vendor' => $vendor], $code);
        }

        return redirect()->route('profile')
            ->with('status', $status)
            ->with('vendor_id', $vendor->vendor_id)
            ->with('success', $message);
    }
}
