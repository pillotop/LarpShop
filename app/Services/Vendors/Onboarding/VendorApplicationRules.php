<?php

namespace App\Services\Vendors\Onboarding;

use App\Enums\Vendors\DeliveryMode;
use App\Enums\Vendors\OnboardingStep;
use App\Enums\Vendors\SellerType;
use App\Models\Vendor;
use App\Support\Countries\CountryCatalog;
use Illuminate\Validation\Rule;

class VendorApplicationRules
{
    public function __construct(private CountryCatalog $countries) {}

    public function draft(): array
    {
        $country = ['nullable', 'string', Rule::in($this->countries->codes())];

        return [
            'current_step' => ['sometimes', 'required', Rule::enum(OnboardingStep::class)],
            'seller_type' => ['nullable', Rule::enum(SellerType::class)],
            'seller_country_code' => $country,
            'business_name' => ['nullable', 'string', 'max:150'],
            'contact_name' => ['nullable', 'string', 'max:150'],
            'contact_email' => ['nullable', 'string', 'email', 'max:100'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'store_name' => ['nullable', 'string', 'min:2', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'pickup_recipient_name' => ['nullable', 'string', 'max:100'],
            'pickup_phone_number' => ['nullable', 'string', 'max:30'],
            'pickup_country_code' => $country,
            'pickup_address_line_1' => ['nullable', 'string', 'max:255'],
            'pickup_address_line_2' => ['nullable', 'string', 'max:255'],
            'pickup_city' => ['nullable', 'string', 'max:100'],
            'pickup_region' => ['nullable', 'string', 'max:100'],
            'pickup_postal_code' => ['nullable', 'string', 'max:30'],
            'delivery_mode' => ['nullable', Rule::enum(DeliveryMode::class)],
            'destination_countries' => ['sometimes', 'array', 'max:249'],
            'destination_countries.*' => ['required', 'string', Rule::in($this->countries->codes())],
        ];
    }

    public function step(OnboardingStep $step, array $data): array
    {
        $rules = $this->draft();
        $required = match ($step) {
            OnboardingStep::Seller => ['seller_type', 'seller_country_code', 'contact_name', 'contact_email', 'contact_phone'],
            OnboardingStep::Store => ['store_name'],
            OnboardingStep::Shipping => ['pickup_recipient_name', 'pickup_phone_number', 'pickup_country_code', 'pickup_address_line_1', 'pickup_city', 'delivery_mode'],
            OnboardingStep::Review => [
                'seller_type', 'seller_country_code', 'contact_name', 'contact_email', 'contact_phone',
                'store_name', 'pickup_recipient_name', 'pickup_phone_number', 'pickup_country_code',
                'pickup_address_line_1', 'pickup_city', 'delivery_mode',
            ],
        };
        foreach ($required as $field) {
            $rules[$field] = ['required', ...$rules[$field]];
        }
        if (in_array($step, [OnboardingStep::Seller, OnboardingStep::Review], true)
            && ($data['seller_type'] ?? null) === SellerType::Business->value) {
            $rules['business_name'][] = 'required';
        }
        if (in_array($step, [OnboardingStep::Shipping, OnboardingStep::Review], true)
            && ($data['delivery_mode'] ?? null) === DeliveryMode::SelectedCountries->value) {
            $rules['destination_countries'] = ['required', 'array', 'min:1', 'max:249'];
        }

        return $rules;
    }

    public function submission(array $data, ?Vendor $vendor): array
    {
        $rules = $this->step(OnboardingStep::Review, $data);
        $unique = Rule::unique('vendors', 'store_name');
        if ($vendor !== null) {
            $unique->ignore($vendor);
        }
        $rules['store_name'][] = $unique;

        return $rules;
    }
}
