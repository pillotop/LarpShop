<?php

namespace App\Http\Requests\Promotions;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SaveProductCollectionRequest extends AdminPromotionRequest
{
    protected function prepareForValidation(): void
    {
        $name = $this->input('name');
        $this->merge([
            'slug' => $this->filled('slug') ? $this->input('slug') : (is_string($name) ? Str::slug($name) : null),
            'is_published' => $this->input('is_published', false),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('product_collections', 'slug')
                    ->ignore($this->route('collection')?->getKey(), 'product_collection_id')],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_published' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('promotions.name'), 'slug' => __('promotions.slug'),
            'description' => __('promotions.description'), 'is_published' => __('promotions.publish_collection'),
        ];
    }
}
