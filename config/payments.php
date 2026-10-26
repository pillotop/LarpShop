<?php

use App\Enums\PaymentMethod;
use App\Services\Payments\FakePaymentGateway;

return [
    'currency' => env('PAYMENT_CURRENCY', 'USD'),
    'providers' => [
        'fake' => [
            'gateway' => FakePaymentGateway::class,
            'methods' => [
                PaymentMethod::Card->value,
                PaymentMethod::EWallet->value,
            ],
        ]
    ]
];
