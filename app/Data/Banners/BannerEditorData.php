<?php

namespace App\Data\Banners;

use App\Models\BannerCard;
use App\Models\BannerGroup;

class BannerEditorData
{
    public static function text(mixed $value): string
    {
        return is_string($value) || is_numeric($value) ? (string) $value : '';
    }

    public function rows(BannerGroup $group): array
    {
        $rows = old('cards', $group->cards->map(fn ($card) => array_merge($card->toArray(), ['id' => $card->getKey()]))->all());
        if (! is_array($rows)) {
            return [];
        }
        $normalized = [];
        foreach (array_slice(array_filter($rows, 'is_array'), 0, 5) as $row) {
            $values = [];
            foreach ([...BannerCard::TEXT_FIELDS, 'link_url', 'presentation', 'text_tone', 'focal_x', 'focal_y'] as $field) {
                if (array_key_exists($field, $row)) {
                    $values[$field] = self::text($row[$field]);
                }
            }
            $values['id'] = is_numeric($row['id'] ?? null) ? (int) $row['id'] : null;
            $values['is_active'] = filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN);
            foreach (BannerCard::TEXT_FIELDS as $field) {
                $translation = is_array($row['translations'] ?? null) && is_array($row['translations']['vi'] ?? null)
                    ? ($row['translations']['vi'][$field] ?? '') : '';
                $values['translations']['vi'][$field] = self::text($translation);
            }
            $normalized[] = $values;
        }

        return $normalized;
    }
}
