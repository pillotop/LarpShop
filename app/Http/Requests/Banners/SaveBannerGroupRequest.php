<?php

namespace App\Http\Requests\Banners;

use App\Models\BannerCard;
use App\Services\Banners\BannerGroupRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveBannerGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->input('is_active', false),
            'cards' => $this->input('cards', []),
            'placements' => $this->input('placements', []),
        ]);
    }

    public function rules(): array
    {
        $rules = app(BannerGroupRules::class)->rules($this->all());
        $rules['slug'][] = Rule::unique('banner_groups', 'slug')->ignore($this->route('group')?->getKey(), 'banner_group_id');
        $rules['cards'][] = 'list';
        $rules['cards.*'] = ['array:id,image,title,eyebrow,cta_text,badge_text,alt_text,translations,link_url,presentation,text_tone,focal_x,focal_y,is_active,sort_order'];
        $rules['cards.*.id'] = ['nullable', 'integer', 'distinct', Rule::exists('banner_cards', 'banner_card_id')
            ->where('banner_group_id', $this->route('group')?->getKey() ?? 0)];
        $rules['cards.*.image'] = ['nullable', 'file', 'mimes:jpg,jpeg,png,webp',
            'max:'.(int) (config('banners.cards_upload.max_bytes') / 1024),
            'dimensions:min_width=320,min_height=320,max_width=8192,max_height=8192'];
        $rules['placement_orders'] = ['sometimes', 'array:'.implode(',', config('banners.placements'))];
        $rules['placement_orders.*'] = ['required', 'integer', 'between:0,1000000'];

        return $rules;
    }

    public function after(): array
    {
        return [function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            foreach ($this->input('cards', []) as $index => $card) {
                if (empty($card['id']) && ! $this->hasFile("cards.$index.image")) {
                    $validator->errors()->add("cards.$index.image", __('banner_groups.image_required'));
                }
            }
        }];
    }

    public function attributes(): array
    {
        $attributes = [];
        foreach (['name', 'slug', 'layout', 'placements'] as $field) {
            $attributes[$field] = __('banner_groups.'.$field);
        }
        foreach (['starts_at', 'expires_at'] as $field) {
            $attributes[$field] = __('banners.'.$field);
        }
        for ($index = 0; $index < 5; $index++) {
            $prefix = __('banner_groups.card').' '.($index + 1).' — ';
            foreach ([...BannerCard::TEXT_FIELDS, 'link_url', 'presentation', 'text_tone', 'focal_x', 'focal_y'] as $field) {
                $attributes["cards.$index.$field"] = $prefix.__('banner_groups.fields.'.$field);
                if (in_array($field, BannerCard::TEXT_FIELDS, true)) {
                    $attributes["cards.$index.translations.vi.$field"] = $prefix.__('banner_groups.fields.'.$field).' (VI)';
                }
            }
            $attributes["cards.$index.image"] = $prefix.__('banner_groups.image');
        }

        return $attributes;
    }

    public function messages(): array
    {
        return [
            'cards.size' => __('banner_groups.publish_count'),
            'cards.*.is_active.accepted' => __('banner_groups.publish_cards'),
            'cards.*.id.exists' => __('banner_groups.invalid_card'),
            'cards.*.id.distinct' => __('banner_groups.invalid_card'),
        ];
    }
}
