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
        return $this->user()?->isVendor() ?? false;
    }

    public function vendor(): Vendor
    {
        return $this->activeVendor ??= $this->user()->activeVendor();
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
                Rule::unique('vendors', 'store_name')->ignore($vendor->vendor_id, 'vendor_id'),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'store_name.required' => 'Vui lòng nhập tên gian hàng.',
            'store_name.min' => 'Tên gian hàng phải có ít nhất 2 ký tự.',
            'store_name.max' => 'Tên gian hàng không được vượt quá 100 ký tự.',
            'store_name.unique' => 'Tên gian hàng này đã được sử dụng bởi người bán khác.',
        ];
    }
}
