<?php

namespace App\Http\Controllers\Payments;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\CreatePaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutPaymentController extends Controller
{
    public function store(CreatePaymentRequest $request, Order $order, PaymentService $payments): JsonResponse|RedirectResponse
    {
        $order = $request->user()
            ->orders()
            ->findOrFail($order->getKey());

        if (! $order->addresses()->where('type', 'shipping')->exists()) {
            throw ValidationException::withMessages(['address_id' => 'A shipping address is required.']);
        }

        $option = config('payments.options.'.$request->validated('payment_option'));
        $payment = $this->attempt(fn () => $payments->createForOrder($order, $option['provider'], PaymentMethod::from($option['method'])));

        return $this->sessionResponse($request, $payment);
    }

    public function retry(Request $request, Payment $payment, PaymentService $payments): JsonResponse|RedirectResponse
    {
        $this->assertOwner($request, $payment);
        $payment = $this->attempt(fn () => $payments->createForOrder($payment->order, $payment->provider, $payment->method));

        return $this->sessionResponse($request, $payment);
    }

    public function returned(Request $request, Payment $payment): View
    {
        $this->assertOwner($request, $payment);

        // Browser query parameters are never proof that money was received.
        return view('checkout.pages.payment-return', compact('payment'));
    }

    public function cancelled(Request $request, Payment $payment): RedirectResponse
    {
        $this->assertOwner($request, $payment);

        return redirect()->route('checkout.show', $payment->order_id)
            ->with('success', 'You left the payment provider. Your order is still awaiting payment.');
    }

    public function complete(Request $request, Payment $payment, PaymentService $payments): JsonResponse|RedirectResponse
    {
        $this->assertOwner($request, $payment);
        $result = $this->attempt(fn () => $payments->complete($payment));

        if ($request->expectsJson()) {
            return response()->json(['status' => $result->status]);
        }

        return redirect()->route('orders.show', $payment->order_id)
            ->with('status', match ($result->status) {
                'succeeded' => 'Payment confirmed. Thank you for your order.',
                'refund_required' => 'Payment received after the order closed. Refund review is required.',
                default => 'Payment status: '.$result->status.'.',
            });
    }

    private function assertOwner(Request $request, Payment $payment): void
    {
        abort_unless((int) $payment->order->user_id === (int) $request->user()->getKey(), 404);
        abort_if($payment->provider === 'fake' && ! app()->environment(['local', 'testing']), 404);
    }

    private function sessionResponse(Request $request, Payment $payment): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'payment_id' => $payment->getKey(), 'status' => $payment->status->value,
                'checkout_url' => $payment->checkout_url,
                'provider' => $payment->provider, 'method' => $payment->method->value,
            ], 201);
        }
        if ($payment->checkout_url !== null) {
            return redirect()->away($payment->checkout_url);
        }

        return redirect()->route('checkout.show', $payment->order_id);
    }

    private function attempt(callable $action): mixed
    {
        try {
            return $action();
        } catch (ConnectionException|RequestException|LockTimeoutException $exception) {
            // Do not expose provider responses or credentials to the browser.
            throw ValidationException::withMessages([
                'payment' => 'The payment provider could not complete this request. Check the payment status or retry shortly.',
            ]);
        }
    }
}
