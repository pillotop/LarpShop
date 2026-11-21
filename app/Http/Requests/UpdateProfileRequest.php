<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'min:3', 'max:50', Rule::unique('users', 'username')->ignore($this->user())],
            'email' => ['required', 'string', 'email', 'max:100', Rule::unique('users', 'email')->ignore($this->user())],
            'phone_number' => ['nullable', 'string', 'max:15'],
            'date_of_birth' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'profile_image' => [
                Rule::prohibitedIf(fn () => $this->boolean('remove_profile_image')),
                'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048',
                'dimensions:max_width=4096,max_height=4096',
            ],
            'remove_profile_image' => ['sometimes', 'boolean'],
        ];
    }
}
