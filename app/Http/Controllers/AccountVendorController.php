<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateVendorRequest;
use App\Http\Requests\VendorRegistrationRequest;
use App\Models\Vendor;
use App\Services\Vendors\VendorManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccountVendorController extends Controller
{
    public function __construct(private VendorManagementService $vendors) {}

    public function store(VendorRegistrationRequest $request): RedirectResponse|JsonResponse
    {
        $vendor = $this->vendors->register($request->user(), $request->validated());

        return $this->respond($request, $vendor, 'vendor-registered', 'Store application submitted for approval.', 201);
    }

    public function update(UpdateVendorRequest $request): RedirectResponse|JsonResponse
    {
        $vendor = $this->vendors->update($request->user(), $request->vendor()->vendor_id, $request->validated());

        return $this->respond($request, $vendor, 'vendor-updated', 'Store profile updated successfully.');
    }

    public function resubmit(UpdateVendorRequest $request): RedirectResponse|JsonResponse
    {
        $vendor = $this->vendors->resubmit($request->user(), $request->vendor()->vendor_id, $request->validated());

        return $this->respond($request, $vendor, 'vendor-registered', 'Store application resubmitted for approval.');
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
