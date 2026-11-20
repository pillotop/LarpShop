<?php

namespace App\Http\Requests\Promotions;

class CollectionSearchRequest extends AdminPromotionRequest
{
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ];
    }
}
