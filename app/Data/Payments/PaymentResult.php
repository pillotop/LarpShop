<?php

namespace App\Data\Payments;

final readonly class PaymentResult
{
    public function __construct(
        public string $reference,
        public string $status,
    ) {}
}
