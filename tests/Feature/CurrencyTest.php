<?php

namespace Tests\Feature;

use App\Services\Currency\ExchangeRateService;
use App\Support\Money\MoneyDisplay;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CurrencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['app.locale' => 'en']);
        request()->setLaravelSession(app('session.store'));
    }

    public function test_currency_and_language_preferences_are_independent(): void
    {
        $this->withSession(['locale' => 'vi'])->from('/products')
            ->post(route('currency.update'), ['currency' => 'VND'])
            ->assertRedirect('/products')
            ->assertSessionHas('display_currency', 'VND')
            ->assertSessionHas('locale', 'vi');

        $this->post(route('locale.update'), ['locale' => 'en'])
            ->assertSessionHas('display_currency', 'VND')
            ->assertSessionHas('locale', 'en');
    }

    public function test_invalid_currency_does_not_replace_the_preference(): void
    {
        $this->withSession(['display_currency' => 'USD'])
            ->postJson(route('currency.update'), ['currency' => 'EUR'])
            ->assertUnprocessable()->assertJsonValidationErrors('currency');
        $this->assertSame('USD', session('display_currency'));
    }

    public function test_usd_does_not_fetch_rates(): void
    {
        $this->assertSame('$1,234.50', app(MoneyDisplay::class)->format('1234.50'));
        Http::assertNothingSent();
    }

    public function test_decimal_conversion_rounds_half_up_and_formats_both_locales(): void
    {
        Http::fake(['open.er-api.com/*' => Http::response([
            'result' => 'success', 'base_code' => 'USD', 'rates' => ['VND' => 25000],
        ])]);
        $this->withSession(['display_currency' => 'VND']);
        $money = app(MoneyDisplay::class);
        $this->assertSame('₫250,250', $money->format('10.01'));
        $this->assertSame('₫13', $money->format('0.0005'));
        app()->setLocale('vi');
        $this->assertSame('250.250 ₫', $money->format('10.01'));
        // A second service instance also uses the cache.
        $this->assertSame('25000', app(ExchangeRateService::class)->usdToVnd());
        Http::assertSentCount(1);
    }

    public function test_unavailable_rates_fall_back_to_usd_and_cache_the_failure_briefly(): void
    {
        Http::fake(['open.er-api.com/*' => Http::failedConnection()]);
        $this->withSession(['display_currency' => 'VND']);
        $money = app(MoneyDisplay::class);
        $this->assertSame('$12.50', $money->format('12.50'));
        $this->assertTrue($money->unavailable());
        $this->assertNull(app(ExchangeRateService::class)->usdToVnd());
        $this->assertSame(['rate' => null], Cache::get(config('currency.cache_key')));
    }

    public function test_invalid_provider_payload_is_not_used_for_conversion(): void
    {
        Http::fake(['open.er-api.com/*' => Http::response([
            'result' => 'success', 'base_code' => 'EUR', 'rates' => ['VND' => -1],
        ])]);
        $this->assertNull(app(ExchangeRateService::class)->usdToVnd());
    }
}
