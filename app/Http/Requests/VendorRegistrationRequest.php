<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VendorRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'min:2', 'max:100', 'unique:vendors,store_name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'store_name.required' => 'Tên cửa hàng không được để trống.',
            'store_name.min' => 'Tên cửa hàng phải có ít nhất :min ký tự.',
            'store_name.max' => 'Tên cửa hàng không được vượt quá :max ký tự.',
            'store_name.unique' => 'Tên cửa hàng này đã tồn tại, vui lòng chọn tên khác.',
            'description.max' => 'Mô tả cửa hàng không được vượt quá :max ký tự.',
        ];
    }
}
