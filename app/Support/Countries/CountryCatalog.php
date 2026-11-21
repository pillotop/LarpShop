<?php

namespace App\Support\Countries;

class CountryCatalog
{
    public function codes(): array
    {
        return array_keys($this->all('en'));
    }

    public function all(?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $countries = trans('countries', [], in_array($locale, ['en', 'vi'], true) ? $locale : 'en');
        asort($countries, SORT_NATURAL | SORT_FLAG_CASE);

        return $countries;
    }
}
