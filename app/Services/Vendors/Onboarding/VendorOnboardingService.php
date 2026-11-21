<?php

namespace App\Services\Vendors\Onboarding;

use App\Enums\Vendors\DeliveryMode;
use App\Enums\Vendors\OnboardingStep;
use App\Enums\Vendors\SellerType;
use App\Enums\Vendors\VendorApplicationStatus;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorApplication;
use App\Queries\Vendors\VendorApplicationQuery;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class VendorOnboardingService
{
    public function __construct(
        private VendorApplicationQuery $applications,
        private VendorApplicationRules $rules,
    ) {}

    public function save(User $user, array $data, ?int $id = null): VendorApplication
    {
        $newPath = null;
        $oldPath = null;

        try {
            $application = DB::transaction(function () use ($user, $data, $id, &$newPath, &$oldPath) {
                $application = $id === null
                    ? $user->vendorApplications()->make()
                    : $this->applications->findOwned($user, $id, lock: true);
                $this->assertEditable($application);
                $this->linkedVendor($application);

                $values = $this->mergedValues($user, $application, $data);
                $rules = isset($data['validate_step'])
                    ? $this->rules->step(OnboardingStep::from($data['validate_step']), $values)
                    : $this->rules->draft();
                Validator::make($values, $rules)->validate();

                $oldPath = $application->brand_path;
                $application->fill(Arr::only($values, $application->getFillable()));
                $application->save();

                if (isset($data['brand_image'])) {
                    $newPath = $data['brand_image']->store($this->imageDirectory($application), 'public');
                    if ($newPath === false) {
                        throw ValidationException::withMessages(['brand_image' => __('onboarding.errors.image')]);
                    }
                    $application->brand_path = $newPath;
                } elseif (! empty($data['remove_brand_image'])) {
                    $application->brand_path = null;
                }
                $application->save();
                $this->syncDestinations($application, $values['destination_countries']);

                return $application->refresh()->load('destinations');
            });
        } catch (Throwable $exception) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }
            throw $exception;
        }

        if ($oldPath !== $application->brand_path) {
            $this->deleteUnusedImage($application, $oldPath);
        }

        return $application;
    }

    public function submit(User $user, int $id): VendorApplication
    {
        $previousVendorImage = null;

        try {
            $application = DB::transaction(function () use ($user, $id, &$previousVendorImage) {
                $application = $this->applications->findOwned($user, $id, lock: true);
                if ($application->status === VendorApplicationStatus::Submitted) {
                    return $application->load('destinations');
                }
                $this->assertEditable($application);
                $vendor = $this->linkedVendor($application);
                $values = $this->mergedValues($user, $application, []);
                Validator::make($values, $this->rules->submission($values, $vendor))->validate();

                $vendor ??= $user->vendors()->make();
                $previousVendorImage = $vendor->brand_path;
                $vendor->fill([
                    'store_name' => $values['store_name'],
                    'description' => $values['description'] ?? null,
                    'status' => 'pending',
                ]);
                $vendor->brand_path = $application->brand_path;
                $vendor->save();

                $application->vendor_id = $vendor->getKey();
                $application->status = VendorApplicationStatus::Submitted;
                $application->current_step = OnboardingStep::Review;
                $application->submitted_at = now();
                $application->save();

                return $application->refresh()->load('destinations');
            });
        } catch (UniqueConstraintViolationException $exception) {
            // The unique index is authoritative if another submission claims the name first.
            if (! str_contains(strtolower($exception->getSql()), 'vendors')) {
                throw $exception;
            }
            throw ValidationException::withMessages(['store_name' => __('onboarding.errors.name_taken')]);
        }

        $this->deleteUnusedImage($application, $previousVendorImage);

        return $application;
    }

    public function resumeRejectedStore(User $user, int $vendorId): VendorApplication
    {
        return DB::transaction(function () use ($user, $vendorId) {
            // Match the review/submission lock order when an application already exists.
            $application = $user->vendorApplications()->where('vendor_id', $vendorId)->lockForUpdate()->first();
            $vendor = $user->vendors()->whereKey($vendorId)->lockForUpdate()->firstOrFail();
            if ($vendor->status !== 'rejected') {
                throw ValidationException::withMessages(['store_name' => __('profile.errors.store_resubmit')]);
            }
            $application ??= $user->vendorApplications()->where('vendor_id', $vendorId)->lockForUpdate()->first();
            if ($application !== null) {
                $this->assertEditable($application);

                return $application;
            }

            $application = $user->vendorApplications()->make([
                'store_name' => $vendor->store_name,
                'description' => $vendor->description,
                'contact_name' => $user->username,
                'contact_email' => $user->email,
                'contact_phone' => $user->phone_number,
            ]);
            $application->vendor_id = $vendor->getKey();
            $application->status = VendorApplicationStatus::Rejected;
            $application->brand_path = $vendor->brand_path;
            $application->save();

            return $application;
        }, attempts: 3);
    }

    private function assertEditable(VendorApplication $application): void
    {
        if (! $application->status->isEditable()) {
            throw ValidationException::withMessages(['application' => __('onboarding.errors.locked')]);
        }
    }

    private function linkedVendor(VendorApplication $application): ?Vendor
    {
        if ($application->vendor_id === null) {
            return null;
        }
        $vendor = Vendor::query()->where('user_id', $application->user_id)
            ->whereKey($application->vendor_id)->lockForUpdate()->firstOrFail();
        if ($application->status !== VendorApplicationStatus::Rejected || $vendor->status !== 'rejected') {
            throw ValidationException::withMessages(['application' => __('onboarding.errors.locked')]);
        }

        return $vendor;
    }

    private function mergedValues(User $user, VendorApplication $application, array $data): array
    {
        $values = array_replace($application->getAttributes(), Arr::only($data, $application->getFillable()));
        if (isset($data['pickup_address_id'])) {
            $address = $user->addresses()->findOrFail($data['pickup_address_id']);
            foreach ($address->snapshot() as $field => $value) {
                $values['pickup_'.$field] = $value;
            }
        }
        if (($values['seller_type'] ?? null) === SellerType::Individual->value) {
            $values['business_name'] = null;
        }
        foreach (['seller_country_code', 'pickup_country_code'] as $field) {
            if (is_string($values[$field] ?? null)) {
                $values[$field] = strtoupper(trim($values[$field]));
            }
        }
        $destinations = $data['destination_countries'] ?? ($application->exists
            ? $application->destinations()->pluck('country_code')->all() : []);
        $values['destination_countries'] = ($values['delivery_mode'] ?? null) === DeliveryMode::SelectedCountries->value
            ? array_values(array_unique($destinations)) : [];

        return $values;
    }

    private function syncDestinations(VendorApplication $application, array $countries): void
    {
        $existing = $application->destinations()->pluck('country_code')->all();
        $application->destinations()->whereNotIn('country_code', $countries)->delete();
        $added = array_values(array_diff($countries, $existing));
        if ($added !== []) {
            $application->destinations()->createMany(array_map(fn ($code) => ['country_code' => $code], $added));
        }
    }

    private function imageDirectory(VendorApplication $application): string
    {
        return 'vendor-applications/'.$application->getKey().'/logos';
    }

    private function deleteUnusedImage(VendorApplication $application, ?string $path): void
    {
        if ($path === null || ! str_starts_with($path, $this->imageDirectory($application).'/')) {
            return;
        }
        if (! Vendor::query()->where('brand_path', $path)->exists()
            && ! VendorApplication::query()->where('brand_path', $path)->exists()) {
            Storage::disk('public')->delete($path);
        }
    }
}
