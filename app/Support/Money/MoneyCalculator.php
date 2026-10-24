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

    public function subtotal(string $unitPrice, int $quantity): BigDecimal
    {
        return BigDecimal::of($unitPrice)
            ->multipliedBy($quantity);
    }

    public function add(BigDecimal $a, BigDecimal $b): BigDecimal
    {
        return $a->plus($b);
    }

    public function format(BigDecimal $amount): string
    {
        return (string) $amount->toScale(
            2,
            RoundingMode::Unnecessary
        );
    }
}
