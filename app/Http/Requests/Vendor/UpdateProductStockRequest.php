<?php

namespace App\Http\Requests\Vendor;

use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductStockRequest extends FormRequest
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
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
