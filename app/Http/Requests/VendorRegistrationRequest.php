<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VendorRegistrationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['vendor_form' => (string) ($this->route('vendor') ?? 'new')]);
    }

    protected function failedValidation(Validator $validator)
    {
        if ($this->expectsJson()) {
            parent::failedValidation($validator);
        }

        // Flash the prepared form identifier, not only the original HTTP input.
        $response = redirect()->to($this->getRedirectUrl())
            ->withInput($this->only(['store_name', 'description', 'vendor_form']))
            ->withErrors($validator, $this->errorBag);

        throw new ValidationException($validator, $response, $this->errorBag);
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'min:2', 'max:100', Rule::unique('vendors', 'store_name')],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
