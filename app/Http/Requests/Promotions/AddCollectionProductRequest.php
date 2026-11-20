<?php

namespace App\Http\Requests\Promotions;

use Illuminate\Validation\Rule;

class AddCollectionProductRequest extends AdminPromotionRequest
{
    public function rules(): array
    {
        return ['product_id' => ['required', 'integer', Rule::exists('products', 'product_id')]];
    }
}
