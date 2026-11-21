<?php

namespace App\Services\Payments\Gateways;

use App\Contracts\Payment\PaymentGateway;
use App\Contracts\Payment\UsesMerchantReference;
use App\Data\Payments\PaymentRequest;
use App\Data\Payments\PaymentResult;
use App\Data\Payments\PaymentSession;
use App\Services\Payments\SandboxPaymentData as Data;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

class ZalopayGateway implements PaymentGateway, UsesMerchantReference
{
    private const BASE_URL = 'https://sb-openapi.zalopay.vn/v2';

    public function referenceFor(PaymentRequest $request): string
    {
        $date = CarbonImmutable::createFromTimestamp($request->createdAt ?? time(), 'Asia/Ho_Chi_Minh');

        return $date->format('ymd').'_larp_'.$request->reference;
    }

    public function createPayment(PaymentRequest $request): PaymentSession
    {
        $reference = $this->referenceFor($request);
        $body = [
            'app_id' => Data::credential('zalopay', 'app_id'),
            'app_trans_id' => $reference,
            'app_user' => 'larpshop',
            'amount' => Data::vnd($request, 'zalopay'),
            'app_time' => ($request->createdAt ?? time()) * 1000,
            'embed_data' => json_encode(['redirecturl' => $request->returnUrl], JSON_THROW_ON_ERROR),
            'item' => '[]',
            'description' => 'LarpShop payment '.$request->reference,
            'callback_url' => route('payments.zalopay.webhook'),
            'bank_code' => '',
        ];
        $body['mac'] = hash_hmac('sha256', Data::fields($body, [
            'app_id', 'app_trans_id', 'app_user', 'amount', 'app_time', 'embed_data', 'item',
        ], '|'), Data::credential('zalopay', 'key1'));
        $response = Http::acceptJson()->asForm()->connectTimeout(5)->timeout(30)
            ->post(self::BASE_URL.'/create', $body)->throw()->json();
        if (! is_array($response)) {
            Data::invalid();
        }
        if (($response['return_code'] ?? null) === 2 && ($response['sub_return_code'] ?? null) === -68) {
            $this->verifyPayment($reference);

            return new PaymentSession($reference, null, 'pending');
        }
        if (($response['return_code'] ?? null) !== 1) {
            Data::invalid();
        }

        return new PaymentSession($reference, Data::checkoutUrl($response['order_url'] ?? null, ['sbgateway.zalopay.vn']), 'pending');
    }

    public function verifyPayment(string $reference): PaymentResult
    {
        $id = $this->paymentId($reference);
        $appId = Data::credential('zalopay', 'app_id');
        $key = Data::credential('zalopay', 'key1');
        $response = Http::acceptJson()->asForm()->connectTimeout(5)->timeout(30)
            ->post(self::BASE_URL.'/query', [
                'app_id' => $appId,
                'app_trans_id' => $reference,
                'mac' => hash_hmac('sha256', $appId.'|'.$reference.'|'.$key, $key),
            ])->throw()->json();
        if (! is_array($response)) {
            Data::invalid();
        }
        $status = match ($response['return_code'] ?? null) {
            1 => 'succeeded',
            3 => ($response['is_processing'] ?? false) ? 'processing' : 'pending',
            2 => match ($response['sub_return_code'] ?? null) {
                -54 => 'cancelled', // The provider payment period expired.
                -63 => 'pending', // The customer can top up and retry the same order.
                default => Data::invalid(), // Query/configuration errors do not prove a failed payment.
            },
            default => Data::invalid(),
        };

        return new PaymentResult($reference, $status,
            $status === 'succeeded' ? Data::integer($response['amount'] ?? null).'.00' : null,
            'VND', $id);
    }

    public function notification(array $body): PaymentResult
    {
        $raw = $body['data'] ?? null;
        $mac = $body['mac'] ?? null;
        if (($body['type'] ?? null) !== 1 || ! is_string($raw) || ! is_string($mac)
            || ! hash_equals(hash_hmac('sha256', $raw, Data::credential('zalopay', 'key2')), $mac)) {
            Data::invalid();
        }
        $data = json_decode($raw, true);
        if (! is_array($data) || (string) ($data['app_id'] ?? '') !== Data::credential('zalopay', 'app_id')) {
            Data::invalid();
        }
        $id = $this->paymentId($data['app_trans_id'] ?? null);

        // ZaloPay sends this callback only for successful payments; the raw data is authenticated with key2.
        return new PaymentResult($data['app_trans_id'], 'succeeded', Data::integer($data['amount'] ?? null).'.00', 'VND', $id);
    }

    private function paymentId(mixed $reference): string
    {
        if (! is_string($reference) || ! preg_match('/^\d{6}_larp_([1-9]\d*)$/D', $reference, $matches)) {
            Data::invalid();
        }

        return $matches[1];
    }
}
