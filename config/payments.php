<?php

use App\Enums\PaymentMethod;
use App\Services\Payments\FakePaymentGateway;
use App\Services\Payments\Gateways\MomoGateway;
use App\Services\Payments\Gateways\VnpayGateway;
use App\Services\Payments\Gateways\ZalopayGateway;
use App\Services\Payments\PayPalPaymentGateway;
use App\Services\Payments\StripePaymentGateway;

return [
    'currency' => env('PAYMENT_CURRENCY', 'USD'),
    'options' => [
        'vnpay' => ['provider' => 'vnpay', 'method' => 'ewallet'],
        'zalopay' => ['provider' => 'zalopay', 'method' => 'ewallet'],
        'momo' => ['provider' => 'momo', 'method' => 'ewallet'],
        'fake_card' => ['provider' => 'fake', 'method' => 'card'],
        'fake_ewallet' => ['provider' => 'fake', 'method' => 'ewallet'],
        'paypal' => ['provider' => 'paypal', 'method' => 'ewallet'],
        'stripe_card' => ['provider' => 'stripe', 'method' => 'card'],
    ],
    'providers' => [
        'vnpay' => [
            'label' => 'VNPAY',
            'gateway' => VnpayGateway::class,
            'minimum_vnd' => 5000,
            'maximum_vnd' => 50000000,
            'enabled' => env('VNPAY_ENABLED', false),
            'redirect' => true,
            'currency' => 'VND',
            'methods' => [PaymentMethod::EWallet->value],
        ],
        'zalopay' => [
            'label' => 'ZaloPay',
            'gateway' => ZalopayGateway::class,
            'minimum_vnd' => 1000,
            'maximum_vnd' => 50000000,
            'enabled' => env('ZALOPAY_ENABLED', false),
            'redirect' => true,
            'currency' => 'VND',
            'methods' => [PaymentMethod::EWallet->value],
        ],
        'momo' => [
            'label' => 'MoMo',
            'gateway' => MomoGateway::class,
            'minimum_vnd' => 1000,
            'maximum_vnd' => 50000000,
            'enabled' => env('MOMO_ENABLED', false),
            'redirect' => true,
            'currency' => 'VND',
            'methods' => [PaymentMethod::EWallet->value],
        ],
        'stripe' => [
            'gateway' => StripePaymentGateway::class,
            'enabled' => env('STRIPE_ENABLED', false),
            'redirect' => true,
            'methods' => [PaymentMethod::Card->value],
        ],
        'fake' => [
            'gateway' => FakePaymentGateway::class,
            'methods' => [
                PaymentMethod::Card->value,
                PaymentMethod::EWallet->value,
            ],
        ],
        'paypal' => [
            'label' => 'PayPal',
            'gateway' => PayPalPaymentGateway::class,
            'redirect' => true,
            'methods' => [
                PaymentMethod::EWallet->value,
            ],
        ],
    ],
];
