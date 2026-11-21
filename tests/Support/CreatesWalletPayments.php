<?php

namespace Tests\Support;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;

trait CreatesWalletPayments
{
    protected array $walletCreates = [];

    protected array $walletQueries = [];

    protected array $walletQueryResults = [];

    protected bool $walletUnavailable = false;

    protected function fakeWallets(): void
    {
        Notification::fake();
        config([
            'payments.currency' => 'USD',
            'payments.providers.momo.enabled' => true,
            'payments.providers.zalopay.enabled' => true,
            'payments.providers.vnpay.enabled' => true,
            'services.momo.partner_code' => 'MOMO_FIXTURE',
            'services.momo.access_key' => 'access-fixture',
            'services.momo.secret_key' => 'momo-fixture',
            'services.zalopay.app_id' => '12345',
            'services.zalopay.key1' => 'zalo-create-fixture',
            'services.zalopay.key2' => 'zalo-callback-fixture',
            'services.vnpay.tmn_code' => 'VNPTEST',
            'services.vnpay.hash_secret' => 'vnpay-fixture',
        ]);
        Cache::put(config('currency.cache_key'), ['rate' => '25000']);
        Http::preventStrayRequests();
        Http::fake([
            'https://test-payment.momo.vn/v2/gateway/api/*' => function (Request $request) {
                if (str_ends_with($request->url(), '/create')) {
                    $this->walletCreates['momo'][] = $request->data();
                    if ($this->walletUnavailable) {
                        return Http::response([], 503);
                    }

                    return Http::response([
                        'resultCode' => 0, 'partnerCode' => $request['partnerCode'],
                        'orderId' => $request['orderId'], 'requestId' => $request['requestId'],
                        'amount' => $request['amount'], 'payUrl' => 'https://test-payment.momo.vn/v2/gateway/pay?t=fixture',
                    ]);
                }
                $this->walletQueries['momo'][] = $request->data();

                return Http::response(array_replace([
                    'partnerCode' => $request['partnerCode'], 'requestId' => $request['requestId'],
                    'orderId' => $request['orderId'], 'amount' => 1248750, 'resultCode' => 0,
                ], $this->walletQueryResults['momo'] ?? []));
            },
            'https://sb-openapi.zalopay.vn/v2/*' => function (Request $request) {
                if (str_ends_with($request->url(), '/create')) {
                    $this->walletCreates['zalopay'][] = $request->data();
                    if ($this->walletUnavailable) {
                        return Http::response([], 503);
                    }

                    return Http::response(['return_code' => 1, 'order_url' => 'https://sbgateway.zalopay.vn/openinapp?order=fixture']);
                }
                $this->walletQueries['zalopay'][] = $request->data();

                return Http::response($this->walletQueryResults['zalopay'] ?? ['return_code' => 1, 'amount' => 1248750]);
            },
            'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction' => function (Request $request) {
                $this->walletQueries['vnpay'][] = $request->data();
                $body = array_replace([
                    'vnp_ResponseId' => 'response-fixture', 'vnp_Command' => 'querydr',
                    'vnp_ResponseCode' => '00', 'vnp_Message' => 'Success', 'vnp_TmnCode' => 'VNPTEST',
                    'vnp_TxnRef' => $request['vnp_TxnRef'], 'vnp_Amount' => '124875000',
                    'vnp_BankCode' => 'NCB', 'vnp_PayDate' => '20261121120000',
                    'vnp_TransactionNo' => '123456', 'vnp_TransactionType' => '01',
                    'vnp_TransactionStatus' => '00', 'vnp_OrderInfo' => $request['vnp_OrderInfo'],
                    'vnp_PromotionCode' => '', 'vnp_PromotionAmount' => '',
                ], $this->walletQueryResults['vnpay'] ?? []);
                $body['vnp_SecureHash'] = hash_hmac('sha512', implode('|', $body), 'vnpay-fixture');

                return Http::response($body);
            },
        ]);
    }

    protected function walletOrder(): Order
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $order = Order::create([
            'user_id' => $user->getKey(), 'total_amount' => '49.95',
            'status' => 'pending', 'expires_at' => now()->addMinutes(30),
        ]);
        $order->addresses()->create([
            'type' => 'shipping', 'recipient_name' => 'Test Buyer',
            'address_line_1' => '123 Test Street', 'city' => 'Test City', 'country_code' => 'VN',
        ]);

        return $order;
    }

    protected function walletPayment(string $provider, ?Order $order = null): Payment
    {
        $order ??= $this->walletOrder();
        $id = $this->postJson(route('payments.store', $order), ['payment_option' => $provider])
            ->assertCreated()->json('payment_id');

        return Payment::findOrFail($id);
    }

    protected function walletNotification(Payment $payment, array $overrides = []): array
    {
        if ($payment->provider === 'momo') {
            $body = array_replace([
                'partnerCode' => 'MOMO_FIXTURE', 'orderId' => $payment->provider_reference,
                'requestId' => 'larpshop-payment-'.$payment->getKey(), 'amount' => 1248750,
                'orderInfo' => 'LarpShop payment '.$payment->getKey(), 'orderType' => 'momo_wallet',
                'transId' => 123456, 'resultCode' => 0, 'message' => 'Success', 'payType' => 'qr',
                'responseTime' => 1795248000000, 'extraData' => '',
            ], $overrides);
            $signed = ['accessKey' => 'access-fixture'] + $body;
            ksort($signed);
            $raw = implode('&', array_map(fn ($key) => $key.'='.$signed[$key], array_keys($signed)));
            $body['signature'] = hash_hmac('sha256', $raw, 'momo-fixture');

            return $body;
        }
        if ($payment->provider === 'zalopay') {
            $raw = json_encode(array_replace([
                'app_id' => 12345, 'app_trans_id' => $payment->provider_reference,
                'amount' => 1248750, 'zp_trans_id' => 123456,
            ], $overrides), JSON_THROW_ON_ERROR);

            return ['data' => $raw, 'mac' => hash_hmac('sha256', $raw, 'zalo-callback-fixture'), 'type' => 1];
        }
        $body = array_replace([
            'vnp_TmnCode' => 'VNPTEST', 'vnp_TxnRef' => $payment->provider_reference,
            'vnp_Amount' => '124875000', 'vnp_TransactionNo' => '123456',
            'vnp_ResponseCode' => '00', 'vnp_TransactionStatus' => '00', 'vnp_OrderInfo' => 'LarpShop payment',
        ], $overrides);
        ksort($body);
        $body['vnp_SecureHash'] = hash_hmac('sha512', http_build_query($body, '', '&', PHP_QUERY_RFC1738), 'vnpay-fixture');

        return $body;
    }

    protected function sendWalletNotification(string $provider, array $body): TestResponse
    {
        // Provider callbacks must work without a browser session or an authenticated customer.
        auth()->forgetGuards();
        if ($provider === 'vnpay') {
            return $this->getJson(route('payments.vnpay.webhook').'?'.http_build_query($body));
        }

        return $this->postJson(route('payments.'.$provider.'.webhook'), $body);
    }
}
