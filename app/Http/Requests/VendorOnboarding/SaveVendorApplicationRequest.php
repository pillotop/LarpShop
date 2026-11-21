<?php

namespace App\Http\Requests\VendorOnboarding;

use App\Enums\Vendors\OnboardingStep;
use App\Queries\Vendors\VendorApplicationQuery;
use App\Services\Vendors\Onboarding\VendorApplicationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveVendorApplicationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $normalized = [];
        foreach (['seller_country_code', 'pickup_country_code'] as $field) {
            if (is_string($this->input($field))) {
                $normalized[$field] = strtoupper(trim($this->input($field)));
            }
        }
        // Multipart forms cannot otherwise represent an explicitly empty array.
        if ($this->input('destination_countries') === '[]') {
            $normalized['destination_countries'] = [];
        } elseif (is_array($this->input('destination_countries'))) {
            $normalized['destination_countries'] = array_map(
                fn ($value) => is_string($value) ? strtoupper(trim($value)) : $value,
                $this->input('destination_countries'),
            );
        }
        $this->merge($normalized);
    }

    public function authorize(): bool
    {
        if ($this->user() === null) {
            return false;
        }
        if ($id = $this->route('application')) {
            app(VendorApplicationQuery::class)->findOwned($this->user(), (int) $id);
        }

        return true;
    }

    public function rules(VendorApplicationRules $rules): array
    {
        return [
            ...$rules->draft(),
            'pickup_address_id' => ['sometimes', 'nullable', 'integer'],
            'validate_step' => ['sometimes', 'required', Rule::in([
                OnboardingStep::Seller->value, OnboardingStep::Store->value, OnboardingStep::Shipping->value,
            ])],
            'brand_image' => [
                Rule::prohibitedIf(fn () => $this->boolean('remove_brand_image')),
                'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048',
                'dimensions:max_width=4096,max_height=4096',
            ],
            'remove_brand_image' => ['sometimes', 'boolean'],
            'user_id' => ['prohibited'],
            'vendor_id' => ['prohibited'],
            'status' => ['prohibited'],
            'brand_path' => ['prohibited'],
            'reviewed_by' => ['prohibited'],
            'reviewed_at' => ['prohibited'],
            'submitted_at' => ['prohibited'],
            'rejection_reason' => ['prohibited'],
        ];
    }
}
