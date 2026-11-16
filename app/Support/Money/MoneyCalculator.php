<?php

namespace App\Support\Money;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final class MoneyCalculator
{
    public function zero(): BigDecimal
    {
        return BigDecimal::zero();
    }

    public function of(string $amount): BigDecimal
    {
        return BigDecimal::of($amount);
    }

    public function subtotal(string $unitPrice, int $quantity): BigDecimal
    {
        return BigDecimal::of($unitPrice)
            ->multipliedBy($quantity);
    }

    public function add(BigDecimal $a, BigDecimal $b): BigDecimal
    {
        return $a->plus($b);
    }

    public function subtract(BigDecimal $a, BigDecimal $b): BigDecimal
    {
        $result = $a->minus($b);

        return $result->isNegative() ? BigDecimal::zero() : $result;
    }

    public function min(BigDecimal $a, BigDecimal $b): BigDecimal
    {
        return $a->isLessThan($b) ? $a : $b;
    }

    public function percentage(BigDecimal $amount, string $percent): BigDecimal
    {
        return $amount
            ->multipliedBy($percent)
            ->dividedBy(100, 2, RoundingMode::HalfUp);
    }

    public function format(BigDecimal $amount): string
    {
        return (string) $amount->toScale(
            2,
            RoundingMode::Unnecessary
        );
    }
}
