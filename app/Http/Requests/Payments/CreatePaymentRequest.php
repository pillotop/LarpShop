<?php

namespace App\Http\Requests\Payments;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePaymentRequest extends FormRequest
{
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
            'payment_option' => [
                'required',
                'string',
                Rule::in(array_keys(array_filter(config('payments.options'),
                    fn (array $option) => $option['provider'] !== 'fake' || app()->environment(['local', 'testing'])
                ))),
            ],
        ];
    }
}
