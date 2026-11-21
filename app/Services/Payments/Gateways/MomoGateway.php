<?php

namespace App\Services\Payments\Gateways;

use App\Contracts\Payment\PaymentGateway;
use App\Contracts\Payment\UsesMerchantReference;
use App\Data\Payments\PaymentRequest;
use App\Data\Payments\PaymentResult;
use App\Data\Payments\PaymentSession;
use App\Services\Payments\SandboxPaymentData as Data;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MomoGateway implements PaymentGateway, UsesMerchantReference
{
    private const BASE_URL = 'https://test-payment.momo.vn/v2/gateway/api';

    private const NOTIFICATION_FIELDS = [
        'accessKey', 'amount', 'extraData', 'message', 'orderId', 'orderInfo',
        'orderType', 'partnerCode', 'payType', 'requestId', 'responseTime', 'resultCode', 'transId',
    ];

    public function referenceFor(PaymentRequest $request): string
    {
        return 'larpshop-'.$request->reference;
    }

    public function createPayment(PaymentRequest $request): PaymentSession
    {
        $reference = $this->referenceFor($request);
        $body = [
            'partnerCode' => Data::credential('momo', 'partner_code'),
            'requestId' => $request->requestId,
            'amount' => Data::vnd($request, 'momo'),
            'orderId' => $reference,
            'orderInfo' => 'LarpShop payment '.$request->reference,
            'redirectUrl' => $request->returnUrl,
            'ipnUrl' => route('payments.momo.webhook'),
            'requestType' => 'captureWallet',
            'extraData' => '',
            'autoCapture' => true,
            'lang' => app()->getLocale() === 'vi' ? 'vi' : 'en',
        ];
        $body['signature'] = $this->signature($body, [
            'accessKey', 'amount', 'extraData', 'ipnUrl', 'orderId', 'orderInfo',
            'partnerCode', 'redirectUrl', 'requestId', 'requestType',
        ]);
        $response = Http::acceptJson()->connectTimeout(5)->timeout(30)
            ->post(self::BASE_URL.'/create', $body)->throw()->json();
        if (! is_array($response)) {
            Data::invalid();
        }
        // A lost response must not generate another merchant order. Status checking remains available.
        if (in_array($response['resultCode'] ?? null, [40, 41], true)) {
            $this->verifyPayment($reference);

            return new PaymentSession($reference, null, 'pending');
        }
        if (($response['resultCode'] ?? null) !== 0
            || ($response['orderId'] ?? null) !== $reference
            || ($response['requestId'] ?? null) !== $request->requestId
            || ($response['partnerCode'] ?? null) !== $body['partnerCode']
            || Data::integer($response['amount'] ?? null) !== $body['amount']) {
            Data::invalid();
        }

        return new PaymentSession($reference, Data::checkoutUrl($response['payUrl'] ?? null, ['test-payment.momo.vn']), 'pending');
    }

    public function verifyPayment(string $reference): PaymentResult
    {
        $this->paymentId($reference);
        $body = [
            'partnerCode' => Data::credential('momo', 'partner_code'),
            'orderId' => $reference,
            'requestId' => (string) Str::uuid(),
            'lang' => 'en',
        ];
        $body['signature'] = $this->signature($body, ['accessKey', 'orderId', 'partnerCode', 'requestId']);
        $response = Http::acceptJson()->connectTimeout(5)->timeout(30)
            ->post(self::BASE_URL.'/query', $body)->throw()->json();
        if (! is_array($response) || ($response['orderId'] ?? null) !== $reference
            || ($response['partnerCode'] ?? null) !== $body['partnerCode']
            || ($response['requestId'] ?? null) !== $body['requestId']) {
            Data::invalid();
        }

        return $this->result($response);
    }

    public function notification(array $body): PaymentResult
    {
        $signature = $body['signature'] ?? null;
        if (! is_string($signature) || ! hash_equals($this->signature($body, self::NOTIFICATION_FIELDS), $signature)
            || ($body['partnerCode'] ?? null) !== Data::credential('momo', 'partner_code')) {
            Data::invalid();
        }
        $paymentId = $this->paymentId($body['orderId'] ?? null);
        if (($body['requestId'] ?? null) !== 'larpshop-payment-'.$paymentId) {
            Data::invalid();
        }

        return $this->result($body);
    }

    private function signature(array $body, array $fields): string
    {
        $body['accessKey'] = Data::credential('momo', 'access_key');

        return hash_hmac('sha256', Data::fields($body, $fields), Data::credential('momo', 'secret_key'));
    }

    private function result(array $body): PaymentResult
    {
        $paymentId = $this->paymentId($body['orderId'] ?? null);
        $status = match ($body['resultCode'] ?? null) {
            0, 9000 => 'succeeded', // captureWallet uses one-step capture.
            1000 => 'pending',
            7000, 7002 => 'processing',
            1006 => 'cancelled',
            1001, 1002, 1003, 1004, 1005, 1007, 1017, 1026, 4001, 4002, 4100 => 'failed',
            default => Data::invalid(), // API/configuration errors are not terminal payment failures.
        };

        return new PaymentResult($body['orderId'], $status, Data::integer($body['amount'] ?? null).'.00', 'VND', $paymentId);
    }

    private function paymentId(mixed $reference): string
    {
        if (! is_string($reference) || ! preg_match('/^larpshop-([1-9]\d*)$/D', $reference, $matches)) {
            Data::invalid();
        }

        return $matches[1];
    }
}
