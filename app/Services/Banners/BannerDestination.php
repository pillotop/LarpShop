<?php

namespace App\Services\Banners;

class BannerDestination
{
    public static function isSafe(mixed $value): bool
    {
        if (! is_string($value) || $value === '' || preg_match('/[\x00-\x20\x7f\\\\]/', $value)) {
            return false;
        }
        if (str_starts_with($value, '/') && ! str_starts_with($value, '//')) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_URL)
            && in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true)
            && parse_url($value, PHP_URL_USER) === null && parse_url($value, PHP_URL_PASS) === null;
    }
}
