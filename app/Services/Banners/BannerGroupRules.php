<?php

namespace App\Services\Banners;

use App\Models\BannerCard;
use Closure;
use Illuminate\Validation\Rule;

class BannerGroupRules
{
    /**
     * Shared validation contract for future management requests and seed imports.
     * Image metadata must be populated by the trusted image processor, not request input.
     */
    public function rules(array $data): array
    {
        $rules = [
            'slug' => ['required', 'string', 'max:255', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/'],
            'name' => ['required', 'string', 'max:255'],
            'layout' => ['required', Rule::in(array_keys(config('banners.layouts')))],
            'is_active' => ['required', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', Rule::when(! empty($data['starts_at']), 'after:starts_at')],
            'cards' => ['present', 'array', 'max:5'],
            'cards.*' => ['array:title,eyebrow,cta_text,badge_text,alt_text,translations,link_url,presentation,text_tone,focal_x,focal_y,is_active,sort_order'],
            'cards.*.title' => ['required', 'string', 'max:255'],
            'cards.*.alt_text' => ['required', 'string', 'max:255'],
            'cards.*.link_url' => ['required', 'string', 'max:2048', $this->safeLink()],
            'cards.*.presentation' => ['required', Rule::in(config('banners.presentations'))],
            'cards.*.text_tone' => ['required', Rule::in(config('banners.text_tones'))],
            'cards.*.focal_x' => ['required', 'integer', 'between:0,100'],
            'cards.*.focal_y' => ['required', 'integer', 'between:0,100'],
            'cards.*.is_active' => ['required', 'boolean'],
            'cards.*.sort_order' => ['required', 'integer', 'between:0,1000000', 'distinct:strict'],
            'cards.*.translations' => ['nullable', 'array:vi'],
            'cards.*.translations.vi' => ['sometimes', 'array:'.implode(',', BannerCard::TEXT_FIELDS)],
            'placements' => ['sometimes', 'array'],
            'placements.*' => ['required', 'string', 'distinct', Rule::in(config('banners.placements'))],
        ];

        foreach (['eyebrow', 'cta_text', 'badge_text'] as $field) {
            $rules['cards.*.'.$field] = ['nullable', 'string', 'max:255'];
        }
        foreach (BannerCard::TEXT_FIELDS as $field) {
            $rules['cards.*.translations.vi.'.$field] = ['nullable', 'string', 'max:255'];
        }

        if (filter_var($data['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $layout = is_string($data['layout'] ?? null) ? $data['layout'] : '';
            $count = config('banners.layouts')[$layout]['card_count'] ?? null;
            if ($count !== null) {
                $rules['cards'][] = 'size:'.$count;
            }
            $rules['cards.*.is_active'][] = 'accepted';
        }

        return $rules;
    }

    private function safeLink(): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail): void {
            if (! BannerDestination::isSafe($value)) {
                $fail('The banner destination must be a local path or an HTTP(S) URL.');
            }
        };
    }
}
