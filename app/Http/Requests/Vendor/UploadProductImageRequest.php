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

        /** @var Product $product */
        $product = $this->route('product');
        $vendor = $user->activeVendor();

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

    public function messages(): array
    {
        return [
            'images.required' => 'Vui lòng chọn ít nhất một hình ảnh để tải lên.',
            'images.array' => 'Dữ liệu hình ảnh tải lên không đúng định dạng.',
            'images.*.image' => 'Tệp tải lên phải là hình ảnh hợp lệ.',
            'images.*.mimes' => 'Hình ảnh phải có định dạng: jpeg, png, jpg, webp, gif.',
            'images.*.max' => 'Kích thước mỗi hình ảnh không được vượt quá 5MB.',
        ];
    }
}
