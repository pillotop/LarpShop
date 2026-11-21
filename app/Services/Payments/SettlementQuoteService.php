<?php

namespace App\Services\Payments;

use App\Services\Currency\ExchangeRateService;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Validation\ValidationException;

class SettlementQuoteService
{
    public function __construct(private ExchangeRateService $rates) {}

    public function rateFor(string $provider): ?string
    {
        return config("payments.providers.{$provider}.currency") === 'VND'
            && config('payments.currency', 'USD') === 'USD'
                ? $this->rates->usdToVnd()
                : '1';
    }

    public function quote(string $amount, string $provider, ?string $rate): array
    {
        $source = config('payments.currency', 'USD');
        $target = config("payments.providers.{$provider}.currency", $source);
        if ($source === $target) {
            if ($target === 'VND') {
                SandboxPaymentData::assertVndAmount(SandboxPaymentData::integer($amount, decimal: true), $provider);
            }

            return ['amount' => $amount, 'currency' => $source];
        }
        if ($source !== 'USD' || $target !== 'VND' || $rate === null) {
            throw ValidationException::withMessages(['payment' => __('checkout.errors.exchange_rate')]);
        }

        $rate = (string) BigDecimal::of($rate)->toScale(8, RoundingMode::HalfUp);
        $settled = BigDecimal::of($amount)->multipliedBy($rate)->toScale(0, RoundingMode::HalfUp);
        SandboxPaymentData::assertVndAmount($settled->toInt(), $provider);

        return [
            'amount' => (string) $settled->toScale(2),
            'currency' => $target,
            'source_amount' => $amount,
            'source_currency' => $source,
            'exchange_rate' => $rate,
        ];
    }
}
