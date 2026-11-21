<?php

namespace Tests\Feature\Payments;

use App\Data\Payments\PaymentRequest;
use App\Enums\PaymentMethod;
use App\Services\Payments\Gateways\MomoGateway;
use App\Services\Payments\Gateways\VnpayGateway;
use App\Services\Payments\Gateways\ZalopayGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesWalletPayments;
use Tests\TestCase;

class WalletProtocolTest extends TestCase
{
    use CreatesWalletPayments, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeWallets();
    }

    public function test_momo_create_and_query_follow_the_documented_signature_formats(): void
    {
        $payment = $this->walletPayment('momo');
        $data = $this->walletCreates['momo'][0];
        $raw = 'accessKey=access-fixture&amount=1248750&extraData=&ipnUrl='.route('payments.momo.webhook')
            .'&orderId='.$payment->provider_reference.'&orderInfo=LarpShop payment '.$payment->getKey()
            .'&partnerCode=MOMO_FIXTURE&redirectUrl='.route('payments.return', $payment)
            .'&requestId=larpshop-payment-'.$payment->getKey().'&requestType=captureWallet';
        $this->assertSame(hash_hmac('sha256', $raw, 'momo-fixture'), $data['signature']);
        $this->assertTrue($data['autoCapture']);
        $this->postJson(route('payments.complete', $payment))->assertOk();
        $query = $this->walletQueries['momo'][0];
        $this->assertSame(hash_hmac('sha256', 'accessKey=access-fixture&orderId='.$payment->provider_reference.'&partnerCode=MOMO_FIXTURE&requestId='.$query['requestId'], 'momo-fixture'), $query['signature']);
    }

    public function test_zalopay_create_and_query_use_key1_and_the_vietnam_date(): void
    {
        $payment = $this->walletPayment('zalopay');
        $data = $this->walletCreates['zalopay'][0];
        $this->assertSame($payment->created_at->setTimezone('Asia/Ho_Chi_Minh')->format('ymd').'_larp_'.$payment->getKey(), $data['app_trans_id']);
        $this->assertSame(route('payments.return', $payment), json_decode($data['embed_data'], true)['redirecturl']);
        $raw = '12345|'.$data['app_trans_id'].'|larpshop|1248750|'.$data['app_time'].'|'.$data['embed_data'].'|[]';
        $this->assertSame(hash_hmac('sha256', $raw, 'zalo-create-fixture'), $data['mac']);
        $this->postJson(route('payments.complete', $payment))->assertOk();
        $this->assertSame(hash_hmac('sha256', '12345|'.$data['app_trans_id'].'|zalo-create-fixture', 'zalo-create-fixture'), $this->walletQueries['zalopay'][0]['mac']);
    }

    public function test_vnpay_signs_urlencoded_fields_and_sends_dong_multiplied_by_one_hundred(): void
    {
        $payment = $this->walletPayment('vnpay');
        parse_str(parse_url($payment->checkout_url, PHP_URL_QUERY), $data);
        $signature = $data['vnp_SecureHash'];
        unset($data['vnp_SecureHash']);
        ksort($data);
        $this->assertSame('124875000', $data['vnp_Amount']);
        $this->assertSame('VND', $data['vnp_CurrCode']);
        $this->assertSame(hash_hmac('sha512', http_build_query($data, '', '&', PHP_QUERY_RFC1738), 'vnpay-fixture'), $signature);
        $this->postJson(route('payments.complete', $payment))->assertOk();
        $query = $this->walletQueries['vnpay'][0];
        $raw = $query['vnp_RequestId'].'|2.1.0|querydr|VNPTEST|'.$payment->provider_reference
            .'|'.$data['vnp_CreateDate'].'|'.$query['vnp_CreateDate'].'|'.$query['vnp_IpAddr'].'|Query LarpShop payment '.$payment->getKey();
        $this->assertSame(hash_hmac('sha512', $raw, 'vnpay-fixture'), $query['vnp_SecureHash']);
    }

    public static function unsafeUrls(): array
    {
        return [
            ['momo', 'https://payment.momo.vn/live'],
            ['momo', 'https://test-payment.momo.vn.attacker.example/pay'],
            ['momo', 'javascript:alert(1)'],
            ['zalopay', 'https://gateway.zalopay.vn/production'],
            ['zalopay', 'https://sbgateway.zalopay.vn@attacker.example/pay'],
        ];
    }

    #[DataProvider('unsafeUrls')]
    public function test_untrusted_or_production_checkout_urls_are_rejected(string $provider, string $url): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake([
            'https://test-payment.momo.vn/v2/gateway/api/create' => fn ($request) => Http::response([
                'resultCode' => 0, 'partnerCode' => $request['partnerCode'], 'orderId' => $request['orderId'],
                'requestId' => $request['requestId'], 'amount' => $request['amount'], 'payUrl' => $url,
            ]),
            'https://sb-openapi.zalopay.vn/v2/create' => Http::response(['return_code' => 1, 'order_url' => $url]),
        ]);
        $this->postJson(route('payments.store', $this->walletOrder()), ['payment_option' => $provider])
            ->assertUnprocessable()->assertJsonValidationErrors('payment');
    }

    public function test_signed_vnpay_query_response_is_required(): void
    {
        $payment = $this->walletPayment('vnpay');
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['https://sandbox.vnpayment.vn/merchant_webapi/api/transaction' => Http::response([
            'vnp_ResponseCode' => '00', 'vnp_TmnCode' => 'VNPTEST', 'vnp_TxnRef' => $payment->provider_reference,
            'vnp_Amount' => '124875000', 'vnp_TransactionStatus' => '00', 'vnp_SecureHash' => 'invalid',
        ])]);
        $this->postJson(route('payments.complete', $payment))->assertUnprocessable();
        $this->assertSame('pending', $payment->fresh()->status->value);
    }

    public function test_zalopay_expiry_allows_a_new_attempt_but_query_errors_do_not(): void
    {
        $payment = $this->walletPayment('zalopay');
        $this->walletQueryResults['zalopay'] = ['return_code' => 2, 'sub_return_code' => -402];
        $this->postJson(route('payments.complete', $payment))->assertUnprocessable();
        $this->assertSame('pending', $payment->fresh()->status->value);
        $this->walletQueryResults['zalopay'] = ['return_code' => 2, 'sub_return_code' => -54];
        $this->postJson(route('payments.complete', $payment))->assertOk()->assertJsonPath('status', 'cancelled');
        $next = $this->walletPayment('zalopay', $payment->order);
        $this->assertNotSame($payment->getKey(), $next->getKey());
    }

    public static function invalidAmounts(): array
    {
        return [
            [MomoGateway::class, 'USD', '49.95'],
            [ZalopayGateway::class, 'VND', '1000.50'],
            [VnpayGateway::class, 'VND', '-10000.00'],
            [MomoGateway::class, 'VND', '999999999999999999999.00'],
        ];
    }

    #[DataProvider('invalidAmounts')]
    public function test_invalid_settlement_amounts_never_reach_provider(string $gateway, string $currency, string $amount): void
    {
        $this->expectException(ValidationException::class);
        try {
            app($gateway)->createPayment(new PaymentRequest('1', $amount, $currency, PaymentMethod::EWallet, 'test', 'https://example.test/return', 'https://example.test/cancel'));
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_signed_callback_preserves_empty_fields_and_whitespace(): void
    {
        $payment = $this->walletPayment('momo');
        $body = $this->walletNotification($payment, ['orderInfo' => ' LarpShop payment ', 'message' => ' Success ']);
        $this->sendWalletNotification('momo', $body)->assertNoContent();
        $this->assertSame('succeeded', $payment->fresh()->status->value);
    }

    public static function duplicateProviders(): array
    {
        return [['momo'], ['zalopay']];
    }

    #[DataProvider('duplicateProviders')]
    public function test_duplicate_initialization_recovers_status_without_creating_another_attempt(string $provider): void
    {
        $this->walletUnavailable = true;
        $order = $this->walletOrder();
        $this->postJson(route('payments.store', $order), ['payment_option' => $provider])->assertUnprocessable();
        $payment = $order->payments()->sole();
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake([
            'https://test-payment.momo.vn/v2/gateway/api/create' => Http::response(['resultCode' => 41]),
            'https://test-payment.momo.vn/v2/gateway/api/query' => fn ($request) => Http::response([
                'partnerCode' => 'MOMO_FIXTURE', 'requestId' => $request['requestId'],
                'orderId' => $payment->provider_reference, 'amount' => 1248750, 'resultCode' => 0,
            ]),
            'https://sb-openapi.zalopay.vn/v2/create' => Http::response(['return_code' => 2, 'sub_return_code' => -68]),
            'https://sb-openapi.zalopay.vn/v2/query' => Http::response(['return_code' => 1, 'amount' => 1248750]),
        ]);
        $this->postJson(route('payments.retry', $payment))->assertCreated()
            ->assertJsonPath('payment_id', $payment->getKey())->assertJsonPath('checkout_url', null);
        $this->postJson(route('payments.complete', $payment))->assertOk()->assertJsonPath('status', 'succeeded');
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame('paid', $order->fresh()->status->value);
    }
}
