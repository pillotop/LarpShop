<?php

namespace App\Http\Requests\Promotions;

use App\Enums\PromoSource;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait ValidatesPromoSource
{
    protected function promoSourceRules(): array
    {
        return ['required', 'string', Rule::in(array_column(PromoSource::cases(), 'value'))];
    }

    protected function promoCollectionRules(bool $required): array
    {
        return [
            Rule::requiredIf($required && $this->input('source') === PromoSource::Collection->value),
            Rule::prohibitedIf($this->input('source') !== PromoSource::Collection->value),
            'nullable', 'integer', Rule::exists('product_collections', 'product_collection_id'),
        ];
    }

    protected function validateSourcePlacement(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }
        $broad = $this->input('placement') === config('promotions.broad_select.placement');
        if (! $broad && $this->input('source') !== PromoSource::Collection->value) {
            $validator->errors()->add('source', __('promotions.source_not_supported'));
        }
        if ($broad && $this->input('layout') !== 'row') {
            $validator->errors()->add('layout', __('promotions.broad_select_layout'));
        }
    }
}
