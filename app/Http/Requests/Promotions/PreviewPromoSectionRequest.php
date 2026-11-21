<?php

namespace App\Http\Requests\Promotions;

use App\Enums\PromoSource;
use App\Models\PromoSection;
use App\Queries\Promotions\PromoSectionQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PreviewPromoSectionRequest extends FormRequest
{
    use ValidatesPromoSource;

    private ?PromoSection $resolvedSection = null;

    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    protected function prepareForValidation(): void
    {
        if (! $this->authorize()) {
            return;
        }
        $id = $this->input('section');
        if ((is_int($id) || is_string($id)) && ctype_digit((string) $id)) {
            $this->resolvedSection = app(PromoSectionQuery::class)->findOptionalForPreview((int) $id);
        }
        $source = $this->input('source', $this->resolvedSection?->source ?? PromoSource::Collection->value);
        $placement = $this->input('placement', $this->resolvedSection?->placement
            ?? ($source === PromoSource::Collection->value ? 'homepage_main' : config('promotions.broad_select.placement')));
        $this->merge([
            'source' => $source,
            'placement' => $placement,
            'layout' => $this->input('layout', $this->resolvedSection?->layout
                ?? ($placement === config('promotions.broad_select.placement') ? 'row' : 'grid')),
            'collection' => $this->input('collection', $source === PromoSource::Collection->value
                ? $this->resolvedSection?->product_collection_id : null),
        ]);
    }

    public function rules(): array
    {
        return [
            'source' => $this->promoSourceRules(),
            'collection' => $this->promoCollectionRules(required: false),
            'section' => ['nullable', 'integer', Rule::exists('promo_sections', 'promo_section_id')],
            'placement' => ['required', 'string', Rule::in(config('promotions.placements'))],
            'layout' => ['required', 'string', Rule::in(array_keys(config('promotions.layouts')))],
        ];
    }

    public function after(): array
    {
        return [fn (Validator $validator) => $this->validateSourcePlacement($validator)];
    }

    public function attributes(): array
    {
        return [
            'source' => __('promotions.source'), 'collection' => __('promotions.collection'),
            'placement' => __('promotions.placement'), 'layout' => __('promotions.layout'),
        ];
    }

    public function previewSection(): ?PromoSection
    {
        return $this->resolvedSection;
    }
}
