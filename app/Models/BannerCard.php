<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BannerCard extends Model
{
    public const TEXT_FIELDS = ['title', 'eyebrow', 'cta_text', 'badge_text', 'alt_text'];

    protected $primaryKey = 'banner_card_id';

    // Group membership is assigned through the relationship.
    protected $fillable = [
        'title', 'eyebrow', 'cta_text', 'badge_text', 'alt_text', 'translations', 'link_url',
        'presentation', 'text_tone', 'focal_x', 'focal_y', 'image_disk', 'image_path',
        'image_width', 'image_height', 'image_variants', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'translations' => 'array', 'image_variants' => 'array', 'is_active' => 'boolean',
            'focal_x' => 'integer', 'focal_y' => 'integer', 'image_width' => 'integer',
            'image_height' => 'integer', 'sort_order' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(BannerGroup::class, 'banner_group_id', 'banner_group_id');
    }

    public function localizedText(string $field, ?string $locale = null): ?string
    {
        if (! in_array($field, self::TEXT_FIELDS, true)) {
            throw new \InvalidArgumentException('Unsupported banner text field.');
        }

        $locale ??= app()->getLocale();
        $translation = $locale === 'en' ? null : ($this->translations[$locale][$field] ?? null);

        return is_string($translation) && trim($translation) !== '' ? $translation : $this->getAttribute($field);
    }
}
