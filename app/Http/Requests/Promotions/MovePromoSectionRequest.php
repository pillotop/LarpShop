<?php

namespace App\Http\Requests\Promotions;

use Illuminate\Validation\Rule;

class MovePromoSectionRequest extends AdminPromotionRequest
{
    public function rules(): array
    {
        return ['direction' => ['required', 'string', Rule::in(['up', 'down'])]];
    }
}
