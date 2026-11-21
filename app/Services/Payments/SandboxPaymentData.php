<?php

namespace App\Services\Payments;

use App\Data\Payments\PaymentRequest;
use App\Enums\PaymentMethod;
use Illuminate\Validation\ValidationException;

final class SandboxPaymentData
{
    public static function credential(string $provider, string $key): string
    {
        $value = config("services.{$provider}.{$key}");
        if (! is_string($value) || trim($value) === '') {
            throw ValidationException::withMessages(['payment' => __('checkout.errors.sandbox_configuration', ['provider' => config("payments.providers.{$provider}.label", $provider)])]);
        }

        return $value;
    }

    public static function vnd(PaymentRequest $request, string $provider): int
    {
        if ($request->currency !== 'VND' || $request->method !== PaymentMethod::EWallet) {
            self::invalid();
        }
        $amount = self::integer($request->amount, decimal: true);
        self::assertVndAmount($amount, $provider);

        return $amount;
    }

    public static function assertVndAmount(int $amount, string $provider): void
    {
        $min = config("payments.providers.{$provider}.minimum_vnd");
        $max = config("payments.providers.{$provider}.maximum_vnd");
        if ($amount < $min || $amount > $max) {
            throw ValidationException::withMessages(['payment' => __('checkout.errors.vnd_amount', ['min' => $min, 'max' => $max])]);
        }
    }

    public static function integer(mixed $value, bool $decimal = false): int
    {
        if ((! is_int($value) && ! is_string($value))
            || ! preg_match($decimal ? '/^\d{1,10}(?:\.00)?$/D' : '/^\d{1,12}$/D', (string) $value)) {
            self::invalid();
        }

        return (int) $value;
    }

    public static function fields(array $data, array $keys, string $separator = '&'): string
    {
        $values = [];
        foreach ($keys as $key) {
            if (! array_key_exists($key, $data) || (! is_string($data[$key]) && ! is_int($data[$key]))) {
                self::invalid();
            }
            $values[] = $separator === '&' ? $key.'='.$data[$key] : (string) $data[$key];
        }

        return implode($separator, $values);
    }

    public static function checkoutUrl(mixed $url, array $hosts): string
    {
        if (! is_string($url) || parse_url($url, PHP_URL_SCHEME) !== 'https'
            || ! in_array(parse_url($url, PHP_URL_HOST), $hosts, true)
            || parse_url($url, PHP_URL_USER) !== null || parse_url($url, PHP_URL_PORT) !== null) {
            self::invalid();
        }

        return $url;
    }

    public static function invalid(): never
    {
        throw ValidationException::withMessages(['payment' => __('checkout.errors.provider_details')]);
    }
}
