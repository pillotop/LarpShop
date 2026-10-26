<?php

namespace App\Services\Payments;

use App\Contracts\Payment\PaymentGateway;
use App\Enums\PaymentMethod;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use LogicException;

class PaymentGatewayFactory
{
    public function __construct(
        private Container $container,
    ) {}

    public function make(string $provider): PaymentGateway
    {
        $class = config("payments.providers.{$provider}.gateway");

        if (! is_string($class) || ! class_exists($class)) {
            throw new InvalidArgumentException(
                "Unsupported payment provider: {$provider}"
            );
        }

        $gateway = $this->container->make($class);

        if (! $gateway instanceof PaymentGateway) {
            throw new LogicException(
                "{$class} must implement PaymentGateway."
            );
        }

        return $gateway;
    }

    public function supports(
        string $provider,
        PaymentMethod $method,
    ): bool {
        $methods = config("payments.providers.{$provider}.methods", []);

        return is_array($methods)
            && in_array($method->value, $methods, true)
            && is_string(
                config("payments.providers.{$provider}.gateway")
            );
    }
}
