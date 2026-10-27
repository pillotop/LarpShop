<?php

namespace App\Http\Requests\Vendor;

use App\Enums\FulfillmentStatus;
use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorOrderFulfillmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->vendor() !== null;
    }

    public function vendor(): ?Vendor
    {
        return $this->attributes->get('active_vendor') ?? $this->user()?->activeVendor();
    }

    public function rules(): array
    {
        return [
            'fulfillment_status' => ['required', 'string', Rule::enum(FulfillmentStatus::class)],
            'order_detail_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
