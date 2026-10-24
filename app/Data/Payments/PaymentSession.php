<?php

namespace App\Data\Payments;

final readonly class PaymentSession
{
    public function __construct(
        public string $reference,
        public ?string $checkoutUrl,
        public string $status,
    ) {}
}
