<?php

namespace App\Http\Requests\Promotions;

class TogglePromoSectionRequest extends AdminPromotionRequest
{
    public function rules(): array
    {
        return ['is_active' => ['required', 'boolean']];
    }
}
