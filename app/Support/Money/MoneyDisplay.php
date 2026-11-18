<?php

namespace App\Support\Money;

use App\Services\Currency\ExchangeRateService;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/** Presentation only. Never use converted amounts to create orders or payments. */
final class MoneyDisplay
{
    private bool $resolved = false;

    private ?string $rate = null;

    public function __construct(private ExchangeRateService $rates) {}

    public function selected(): string
    {
        $value = request()->hasSession() ? request()->session()->get('display_currency', 'USD') : 'USD';

        return in_array($value, config('currency.supported'), true) ? $value : 'USD';
    }

    public function currency(): string
    {
        if ($this->selected() === 'USD') {
            return 'USD';
        }

        if (! $this->resolved) {
            $this->rate = $this->rates->usdToVnd();
            $this->resolved = true;
        }

        return $this->rate === null ? 'USD' : 'VND';
    }

    public function unavailable(): bool
    {
        return $this->selected() !== $this->currency();
    }

    public function format(string|int|float $amount): string
    {
        $currency = $this->currency();
        $converted = BigDecimal::of((string) $amount)
            ->multipliedBy($currency === 'VND' ? $this->rate : '1')
            ->toScale($currency === 'VND' ? 0 : 2, RoundingMode::HalfUp);

        // Both supported locales can be formatted without floats or an ext-intl dependency.
        $vietnamese = $currency === 'VND' && app()->getLocale() === 'vi';
        $parts = explode('.', (string) $converted);
        $whole = preg_replace('/\B(?=(\d{3})+(?!\d))/', $vietnamese ? '.' : ',', $parts[0]);
        $number = $whole.(isset($parts[1]) ? ($vietnamese ? ',' : '.').$parts[1] : '');

        return match (true) {
            $currency === 'VND' => $vietnamese ? $number.' ₫' : '₫'.$number,
            default => '$'.$number,
        };
    }
}
