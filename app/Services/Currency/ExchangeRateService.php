<?php

namespace App\Services\Currency;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class ExchangeRateService
{
    public function usdToVnd(): ?string
    {
        $key = config('currency.cache_key');
        $cached = Cache::get($key);

        if (is_array($cached)) {
            return $cached['rate'];
        }

        $rate = null;

        try {
            $response = Http::acceptJson()->connectTimeout(2)->timeout(3)
                ->get(config('currency.rates_url'));
            $value = $response->json('rates.VND');

            if ($response->successful()
                && $response->json('result') === 'success'
                && $response->json('base_code') === 'USD'
                && is_numeric($value) && $value > 0 && $value < 1000000) {
                $rate = (string) $value;
            }
        } catch (ConnectionException) {
            // An unavailable provider must not prevent browsing or checkout.
        }

        Cache::put($key, ['rate' => $rate], $rate === null ? now()->addMinutes(5) : now()->addDay());

        return $rate;
    }
}
