<?php

namespace App\Http\Requests\Vendor;

use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStoreSettingsRequest extends FormRequest
{
    private ?Vendor $activeVendor = null;

    public function authorize(): bool
    {
        return $this->vendor() !== null;
    }

    public function vendor(): ?Vendor
    {
        return $this->activeVendor ??= ($this->attributes->get('active_vendor') ?? $this->user()?->activeVendor());
    }

    public function rules(): array
    {
        $vendor = $this->vendor();

        return [
            'store_name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                Rule::unique('vendors', 'store_name')->ignore($vendor?->vendor_id, 'vendor_id'),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
