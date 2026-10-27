<?php

use App\Enums\PaymentMethod;
use App\Services\Payments\FakePaymentGateway;
use App\Services\Payments\PayPalPaymentGateway;

return [
    'currency' => env('PAYMENT_CURRENCY', 'USD'),
    'options' => [
        'fake_card' => ['provider' => 'fake', 'method' => 'card'],
        'fake_ewallet' => ['provider' => 'fake', 'method' => 'ewallet'],
        'paypal' => ['provider' => 'paypal', 'method' => 'ewallet'],
    ],
    'providers' => [
        'fake' => [
            'gateway' => FakePaymentGateway::class,
            'methods' => [
                PaymentMethod::Card->value,
                PaymentMethod::EWallet->value,
            ],
        ],
        'paypal' => [
            'gateway' => PayPalPaymentGateway::class,
            'redirect' => true,
            'methods' => [
                PaymentMethod::EWallet->value,
            ],
        ],
    ],
];
