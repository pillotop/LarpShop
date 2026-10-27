<?php

namespace App\Http\Requests\Vendor;

use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVendorProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $vendor = $this->vendor();
        /** @var Product|null $product */
        $product = $this->route('product');

        if (! $vendor || ! $product) {
            return false;
        }

        return (int) $product->vendor_id === (int) $vendor->vendor_id;
    }

    public function vendor(): ?Vendor
    {
        return $this->attributes->get('active_vendor') ?? $this->user()?->activeVendor();
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => $this->boolean('is_active'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'slug' => ['nullable', 'string', 'max:150'],
            'category_id' => ['required', 'integer', 'exists:categories,category_id'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
