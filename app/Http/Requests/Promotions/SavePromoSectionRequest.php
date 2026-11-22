<?php

namespace App\Http\Requests\Promotions;

use App\Enums\PromoSource;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SavePromoSectionRequest extends AdminPromotionRequest
{
    use ValidatesPromoSource;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'source' => $this->input('source', PromoSource::Collection->value),
            'is_active' => $this->input('is_active', false),
            'remove_image' => $this->input('remove_image', false),
        ]);
    }

    public function rules(): array
    {
        return [
            'source' => $this->promoSourceRules(),
            'product_collection_id' => $this->promoCollectionRules(required: true),
            'heading' => ['nullable', 'string', 'max:150'],
            'banner_group_id' => ['nullable', 'integer', Rule::exists('banner_groups', 'banner_group_id')],
            'introduction' => ['nullable', 'string', 'max:5000'],
            'placement' => ['required', 'string', Rule::in(config('promotions.placements'))],
            'layout' => ['required', 'string', Rule::in(array_keys(config('promotions.layouts')))],
            'is_active' => ['required', 'boolean'],
            'starts_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'expires_at' => ['nullable', 'date_format:Y-m-d\TH:i', Rule::when($this->filled('starts_at'), 'after:starts_at')],
            'remove_image' => ['required', 'boolean'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'promo_image' => ['nullable', 'file', 'mimes:png,webp',
                'max:'.(int) (config('promotions.upload.max_cropped_bytes') / 1024)],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $this->validateSourcePlacement($validator);
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $profile = config('promotions.layouts')[$this->input('layout')]['image_profile'];
            if ($profile === null) {
                if ($this->hasFile('promo_image')) {
                    $validator->errors()->add('promo_image', __('promotions.image_not_supported'));
                }

                return;
            }
            if (! $this->hasFile('promo_image')
                && (! $this->route('section')?->image_path || $this->boolean('remove_image'))) {
                $validator->errors()->add('promo_image', __('promotions.image_required'));
            }
            if (! $this->filled('alt_text')) {
                $validator->errors()->add('alt_text', __('promotions.alt_required'));
            }
        }];
    }

    public function attributes(): array
    {
        return [
            'source' => __('promotions.source'),
            'product_collection_id' => __('promotions.collection'), 'layout' => __('promotions.layout'),
            'placement' => __('promotions.placement'), 'heading' => __('promotions.heading'),
            'banner_group_id' => __('banner_groups.attachment'),
            'starts_at' => __('promotions.starts_at'), 'expires_at' => __('promotions.expires_at'),
        ];
    }
}
