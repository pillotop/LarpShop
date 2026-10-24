<?php

namespace App\Http\Requests\Admin;

use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUpdateVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        /** @var Vendor $vendor */
        $vendor = $this->route('vendor');
        $vendorId = $vendor instanceof Vendor ? $vendor->vendor_id : $vendor;

        return [
            'store_name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                Rule::unique('vendors', 'store_name')->ignore($vendorId, 'vendor_id'),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'string', Rule::in(['pending', 'approved', 'active', 'rejected', 'suspended'])],
        ];
    }
}
