<?php

namespace App\Services\Vendors\Onboarding;

use App\Enums\Vendors\VendorApplicationStatus;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorApplication;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VendorApplicationReviewService
{
    public function decide(User $reviewer, int $vendorId, bool $approve, ?string $reason = null): void
    {
        abort_unless($reviewer->isAdmin(), 403);

        DB::transaction(function () use ($reviewer, $vendorId, $approve, $reason) {
            [$vendor, $application] = $this->lockStore($vendorId);
            $this->requireState($vendor->status === 'pending'
                && ($application === null || $application->status === VendorApplicationStatus::Submitted));

            if (! $approve && trim($reason ?? '') === '') {
                throw ValidationException::withMessages(['rejection_reason' => __('onboarding.review.reason_required')]);
            }
            // A real rejection of a legacy store creates a partial review record, not invented onboarding data.
            if ($application === null && ! $approve) {
                $application = $vendor->application()->make([
                    'store_name' => $vendor->store_name,
                    'description' => $vendor->description,
                ]);
                $application->user_id = $vendor->user_id;
                $application->brand_path = $vendor->brand_path;
            }
            if ($application !== null) {
                $application->status = $approve ? VendorApplicationStatus::Approved : VendorApplicationStatus::Rejected;
                $application->reviewed_by = $reviewer->getKey();
                $application->reviewed_at = now();
                $application->rejection_reason = $approve ? null : trim($reason);
                $application->save();
            }
            $vendor->update(['status' => $approve ? 'approved' : 'rejected']);
        }, attempts: 3);
    }

    public function changeAvailability(User $reviewer, int $vendorId, bool $suspend): void
    {
        abort_unless($reviewer->isAdmin(), 403);

        DB::transaction(function () use ($vendorId, $suspend) {
            [$vendor, $application] = $this->lockStore($vendorId);
            $allowed = $suspend ? ['active', 'approved'] : ['suspended'];
            $this->requireState(in_array($vendor->status, $allowed, true)
                && ($application === null || $application->status === VendorApplicationStatus::Approved));
            $vendor->update(['status' => $suspend ? 'suspended' : 'approved']);
        }, attempts: 3);
    }

    public function configure(User $reviewer, int $vendorId, array $data): void
    {
        abort_unless($reviewer->isAdmin(), 403);

        DB::transaction(function () use ($vendorId, $data) {
            [$vendor, $application] = $this->lockStore($vendorId);
            $this->requireState(in_array($vendor->status, ['approved', 'active', 'suspended'], true)
                && ($application === null || $application->status === VendorApplicationStatus::Approved));
            // Onboarding remains a review snapshot; configuration changes only the live store.
            $vendor->update(Arr::only($data, ['store_name', 'description']));
        }, attempts: 3);
    }

    private function lockStore(int $id): array
    {
        // Use the same application-before-vendor lock order as owner submission.
        $application = VendorApplication::query()->where('vendor_id', $id)->lockForUpdate()->first();
        $vendor = Vendor::query()->lockForUpdate()->findOrFail($id);
        $application ??= VendorApplication::query()->where('vendor_id', $id)->lockForUpdate()->first();
        $this->requireState($application === null || $application->user_id === $vendor->user_id);

        return [$vendor, $application];
    }

    private function requireState(bool $allowed): void
    {
        if (! $allowed) {
            throw ValidationException::withMessages(['status' => __('onboarding.review.invalid_transition')]);
        }
    }
}
