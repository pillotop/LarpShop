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
use Illuminate\Support\Str;

class VnpayGateway implements PaymentGateway, UsesMerchantReference
{
    private const QUERY_RESPONSE_FIELDS = [
        'vnp_ResponseId', 'vnp_Command', 'vnp_ResponseCode', 'vnp_Message', 'vnp_TmnCode',
        'vnp_TxnRef', 'vnp_Amount', 'vnp_BankCode', 'vnp_PayDate', 'vnp_TransactionNo',
        'vnp_TransactionType', 'vnp_TransactionStatus', 'vnp_OrderInfo', 'vnp_PromotionCode', 'vnp_PromotionAmount',
    ];

    public function referenceFor(PaymentRequest $request): string
    {
        return $this->date($request->createdAt)->format('YmdHis').'-'.$request->reference;
    }

    public function createPayment(PaymentRequest $request): PaymentSession
    {
        $reference = $this->referenceFor($request);
        $date = $this->date($request->createdAt);
        $fields = [
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => Data::credential('vnpay', 'tmn_code'),
            'vnp_Amount' => Data::vnd($request, 'vnpay') * 100,
            'vnp_CurrCode' => 'VND',
            'vnp_TxnRef' => $reference,
            'vnp_OrderInfo' => 'LarpShop payment '.$request->reference,
            'vnp_OrderType' => 'other',
            'vnp_Locale' => app()->getLocale() === 'vi' ? 'vn' : 'en',
            'vnp_ReturnUrl' => $request->returnUrl,
            'vnp_IpAddr' => request()->ip() ?? '127.0.0.1',
            'vnp_CreateDate' => $date->format('YmdHis'),
            'vnp_ExpireDate' => $date->addMinutes(15)->format('YmdHis'),
        ];
        $query = $this->encoded($fields);

        return new PaymentSession($reference,
            'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?'.$query.'&vnp_SecureHash='.$this->hash($query), 'pending');
    }

    public function verifyPayment(string $reference): PaymentResult
    {
        $id = $this->paymentId($reference);
        $body = [
            'vnp_RequestId' => str_replace('-', '', (string) Str::uuid()),
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'querydr',
            'vnp_TmnCode' => Data::credential('vnpay', 'tmn_code'),
            'vnp_TxnRef' => $reference,
            'vnp_TransactionDate' => substr($reference, 0, 14),
            'vnp_CreateDate' => $this->date()->format('YmdHis'),
            'vnp_IpAddr' => request()->ip() ?? '127.0.0.1',
            'vnp_OrderInfo' => 'Query LarpShop payment '.$id,
        ];
        $body['vnp_SecureHash'] = $this->hash(Data::fields($body, [
            'vnp_RequestId', 'vnp_Version', 'vnp_Command', 'vnp_TmnCode', 'vnp_TxnRef',
            'vnp_TransactionDate', 'vnp_CreateDate', 'vnp_IpAddr', 'vnp_OrderInfo',
        ], '|'));
        $response = Http::acceptJson()->connectTimeout(5)->timeout(30)
            ->post('https://sandbox.vnpayment.vn/merchant_webapi/api/transaction', $body)->throw()->json();
        if (! is_array($response)) {
            Data::invalid();
        }
        // Optional response fields contribute empty strings to the checksum.
        $signed = array_replace(array_fill_keys(self::QUERY_RESPONSE_FIELDS, ''), $response);
        $signature = $response['vnp_SecureHash'] ?? null;
        if (! is_string($signature) || ! hash_equals($this->hash(Data::fields($signed, self::QUERY_RESPONSE_FIELDS, '|')), $signature)
            || ($response['vnp_TmnCode'] ?? null) !== $body['vnp_TmnCode']
            || ($response['vnp_TxnRef'] ?? null) !== $reference
            || ($response['vnp_Command'] ?? null) !== 'querydr') {
            Data::invalid();
        }
        if (($response['vnp_ResponseCode'] ?? null) === '91') {
            return new PaymentResult($reference, 'pending', currency: 'VND', merchantReference: $id);
        }
        if (($response['vnp_ResponseCode'] ?? null) !== '00') {
            Data::invalid();
        }

        return $this->result($response);
    }

    public function notification(array $body): PaymentResult
    {
        $fields = array_filter($body, fn ($key) => str_starts_with($key, 'vnp_'), ARRAY_FILTER_USE_KEY);
        $signature = $fields['vnp_SecureHash'] ?? null;
        unset($fields['vnp_SecureHash'], $fields['vnp_SecureHashType']);
        if (! is_string($signature) || ! hash_equals($this->hash($this->encoded($fields)), $signature)
            || ($fields['vnp_TmnCode'] ?? null) !== Data::credential('vnpay', 'tmn_code')) {
            Data::invalid();
        }

        return $this->result($fields, notification: true);
    }

    private function result(array $fields, bool $notification = false): PaymentResult
    {
        $id = $this->paymentId($fields['vnp_TxnRef'] ?? null);
        $status = match ($fields['vnp_TransactionStatus'] ?? null) {
            '00' => 'succeeded',
            '01' => 'pending',
            '02' => $notification && ($fields['vnp_ResponseCode'] ?? null) === '24' ? 'cancelled' : 'failed',
            '04', '07' => 'processing', // Reversal/suspected fraud must never fulfill an order.
            default => Data::invalid(),
        };
        if ($notification && $status === 'succeeded' && ($fields['vnp_ResponseCode'] ?? null) !== '00') {
            Data::invalid();
        }
        $amount = Data::integer($fields['vnp_Amount'] ?? null);
        if ($amount % 100 !== 0) {
            Data::invalid();
        }

        return new PaymentResult($fields['vnp_TxnRef'], $status, intdiv($amount, 100).'.00', 'VND', $id);
    }

    private function encoded(array $fields): string
    {
        // Reject arrays before canonicalization; PHP and VNPAY must sign the same flat fields.
        Data::fields($fields, array_keys($fields));
        ksort($fields);

        return http_build_query($fields, '', '&', PHP_QUERY_RFC1738);
    }

    private function hash(string $input): string
    {
        return hash_hmac('sha512', $input, Data::credential('vnpay', 'hash_secret'));
    }

    private function date(?int $timestamp = null): CarbonImmutable
    {
        return CarbonImmutable::createFromTimestamp($timestamp ?? time(), 'Asia/Ho_Chi_Minh');
    }

    private function paymentId(mixed $reference): string
    {
        if (! is_string($reference) || ! preg_match('/^\d{14}-([1-9]\d*)$/D', $reference, $matches)) {
            Data::invalid();
        }

        return $matches[1];
    }
}
