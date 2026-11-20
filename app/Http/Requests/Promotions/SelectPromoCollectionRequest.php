<?php

namespace App\Http\Requests\Promotions;

use Illuminate\Validation\Rule;

class SelectPromoCollectionRequest extends AdminPromotionRequest
{
    public function rules(): array
    {
        return ['collection' => ['nullable', 'integer', Rule::exists('product_collections', 'product_collection_id')]];
    }
}
