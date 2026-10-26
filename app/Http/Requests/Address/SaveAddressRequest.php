<?php

namespace App\Http\Requests\Address;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class SaveAddressRequest extends FormRequest
{
    protected $errorBag = 'billing';

    protected function getRedirectUrl(): string
    {
        return route('profile').'#pane-billing';
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['address_form' => (string) ($this->route('address')?->getKey() ?? 'new')]);
    }

    protected function failedValidation(Validator $validator)
    {
        if ($this->expectsJson()) {
            parent::failedValidation($validator);
        }

        $response = redirect()->to($this->getRedirectUrl())
            ->withInput($this->only([...array_keys($this->rules()), 'address_form']))
            ->withErrors($validator, $this->errorBag);

        throw new ValidationException($validator, $response, $this->errorBag);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'recipient_name' => [
                'required', 'string', 'max:100',
            ],
            'phone_number' => [
                'nullable', 'string', 'max:30',
            ],
            'address_line_1' => [
                'required', 'string', 'max:255',
            ],
            'address_line_2' => [
                'nullable', 'string', 'max:255',
            ],
            'city' => [
                'required', 'string', 'max:100',
            ],
            'region' => [
                'nullable', 'string', 'max:100',
            ],
            'postal_code' => [
                'nullable', 'string', 'max:30',
            ],
            'country_code' => [
                'required', 'string', 'size:2', 'alpha',
            ],
        ];
    }
}
