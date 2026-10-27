<?php

namespace App\Data\Payments;

final readonly class PaymentResult
{
    public function __construct(
        public string $reference,
        public string $status,
        public ?string $amount = null,
        public ?string $currency = null,
        public ?string $merchantReference = null,
    ) {}
}
