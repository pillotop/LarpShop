<?php

namespace App\Http\Requests\Vendor;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class UploadProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user || ! $user->isVendor()) {
            return false;
        }

        /** @var Product|null $product */
        $product = $this->route('product');
        $vendor = $this->attributes->get('active_vendor') ?? $user->activeVendor();

        if (! $product || ! $vendor) {
            return false;
        }

        return (int) $product->vendor_id === (int) $vendor->vendor_id || $user->isAdmin();
    }

    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['required', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:5120'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ];
    }
}
