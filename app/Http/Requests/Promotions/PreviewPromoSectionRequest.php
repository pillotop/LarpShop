<?php

namespace App\Http\Requests\Promotions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreviewPromoSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'collection' => ['nullable', 'integer', Rule::exists('product_collections', 'product_collection_id')],
            'section' => ['nullable', 'integer', Rule::exists('promo_sections', 'promo_section_id')],
            'layout' => ['nullable', 'string', Rule::in(array_keys(config('promotions.layouts')))],
        ];
    }
}
