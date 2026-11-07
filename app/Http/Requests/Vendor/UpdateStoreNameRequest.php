<?php

namespace App\Http\Requests\Vendor;

use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStoreNameRequest extends FormRequest
{
    public function authorize(): bool
    {
        $vendor = $this->route('vendor');

        return $vendor instanceof Vendor
            && $vendor->user_id === $this->user()?->user_id
            && in_array($vendor->status, ['active', 'approved'], true);
    }

    public function rules(): array
    {
        return [
            'store_name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                Rule::unique('vendors', 'store_name')->ignore($this->route('vendor')->vendor_id, 'vendor_id'),
            ],
        ];
    }
}
