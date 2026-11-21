<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Services\Payments\Gateways\MomoGateway;
use App\Services\Payments\Gateways\VnpayGateway;
use App\Services\Payments\Gateways\ZalopayGateway;
use App\Services\Payments\SandboxNotificationHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class WalletWebhookController extends Controller
{
    public function momo(Request $request, MomoGateway $gateway, SandboxNotificationHandler $handler): Response
    {
        abort_unless(config('payments.providers.momo.enabled'), 404);
        try {
            $payload = $this->jsonPayload($request);
            $result = $gateway->notification($payload);
            $handler->handle('momo', $result, $payload['signature']);
        } catch (ValidationException) {
            return response('', 400);
        }

        return response()->noContent();
    }

    public function zalopay(Request $request, ZalopayGateway $gateway, SandboxNotificationHandler $handler): JsonResponse
    {
        abort_unless(config('payments.providers.zalopay.enabled'), 404);
        try {
            $payload = $this->jsonPayload($request);
            $result = $gateway->notification($payload);
            $handler->handle('zalopay', $result, $payload['mac']);
        } catch (ValidationException) {
            return response()->json(['return_code' => 2, 'return_message' => 'Invalid']);
        }

        return response()->json(['return_code' => 1, 'return_message' => 'Success']);
    }

    public function vnpay(Request $request, VnpayGateway $gateway, SandboxNotificationHandler $handler): JsonResponse
    {
        abort_unless(config('payments.providers.vnpay.enabled'), 404);
        // Preserve signed whitespace and empty fields that input-normalizing middleware changes.
        parse_str($request->server('QUERY_STRING', ''), $payload);
        try {
            $result = $gateway->notification($payload);
        } catch (ValidationException) {
            return $this->vnpayResponse('97', 'Invalid signature or payload');
        }
        try {
            $handler->handle('vnpay', $result, $payload['vnp_SecureHash']);
        } catch (ValidationException) {
            return $this->vnpayResponse('04', 'Payment details do not match');
        } catch (HttpException $exception) {
            return $exception->getStatusCode() === 404
                ? $this->vnpayResponse('01', 'Payment not found')
                : $this->vnpayResponse('99', 'Please retry later');
        }

        return $this->vnpayResponse('00', 'Success');
    }

    private function vnpayResponse(string $code, string $message): JsonResponse
    {
        return response()->json(['RspCode' => $code, 'Message' => $message]);
    }

    private function jsonPayload(Request $request): array
    {
        $body = json_decode($request->getContent(), true);
        if (! is_array($body)) {
            throw ValidationException::withMessages(['payment' => __('checkout.errors.provider_details')]);
        }

        return $body;
    }
}
