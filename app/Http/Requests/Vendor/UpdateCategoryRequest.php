<?php

namespace App\Http\Requests\Vendor;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user || ! $user->isVendor()) {
            return false;
        }

        /** @var Category $category */
        $category = $this->route('category');
        $vendor = $user->activeVendor();

        if (! $category || ! $vendor) {
            return false;
        }

        // Only owner vendor or admin can update
        return (int) $category->vendor_id === (int) $vendor->vendor_id || $user->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,category_id'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên danh mục.',
            'name.min' => 'Tên danh mục phải có ít nhất 2 ký tự.',
            'name.max' => 'Tên danh mục không được vượt quá 100 ký tự.',
            'parent_id.exists' => 'Danh mục cha đã chọn không tồn tại trong hệ thống.',
        ];
    }
}
