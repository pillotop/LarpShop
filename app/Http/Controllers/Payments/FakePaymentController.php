<?php

namespace App\Http\Controllers\Payments;

use App\Enums\FakePaymentOutcome;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\FakePaymentGateway;
use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FakePaymentController extends Controller
{
    public function pay(Request $request, Order $order, PaymentService $payments, FakePaymentGateway $gateway): RedirectResponse
    {
        abort_unless(
            (int) $order->user_id === (int) $request->user()->getKey(),
            404,
        );

        $validated = $request->validate([
            'terms' => ['accepted'],
            'provider' => ['sometimes', 'required', 'string', Rule::in(['fake'])],
            'method' => ['sometimes', 'required', 'string', Rule::enum(PaymentMethod::class)],
        ]);

        $result = DB::transaction(function () use ($order, $payments, $gateway, $validated) {

            $order = Order::query()
                ->whereKey($order
                ->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $order->addresses()->where('type', 'shipping')->exists()) {
                throw ValidationException::withMessages([
                    'address_id' => 'A shipping address is required.',
                ]);
            }

            $payment = $payments->createForOrder(
                $order,
                provider: 'fake',
                method: PaymentMethod::from($validated['method'] ?? PaymentMethod::Card->value),
            );

            $gateway->simulate($payment->provider_reference, FakePaymentOutcome::Succeeded);

            return $payments->verify($payment);
        }, attempts: 3);

        return redirect()->route('checkout.show', $order)->with('success', $result->status === 'succeeded'
            ? 'Demo payment completed. No money was charged.'
            : 'Demo payment received after the order closed. Refund review is required.');
    }

    public function store(
        Request $request,
        Order $order,
        PaymentService $payments,
    ): JsonResponse {
        abort_unless(
            (int) $order->user_id === (int) $request->user()->user_id,
            404,
        );

        $payment = $payments->createForOrder(
            $order,
            provider: 'fake',
            method: PaymentMethod::Card,
        );

        return response()->json([
            'payment_id' => $payment->payment_id,
            'reference' => $payment->provider_reference,
            'status' => $payment->status,
        ], 201);
    }

    public function simulate(
        Request $request,
        Payment $payment,
        FakePaymentGateway $gateway,
        PaymentService $payments,
    ): JsonResponse {
        $this->assertOwner($request, $payment);

        $data = $request->validate([
            'outcome' => ['required', Rule::enum(FakePaymentOutcome::class)],
        ]);

        $gateway->simulate(
            $payment->provider_reference,
            FakePaymentOutcome::from($data['outcome']),
        );

        $result = $payments->verify($payment);

        return response()->json([
            'status' => $result->status,
        ]);
    }

    public function verify(
        Request $request,
        Payment $payment,
        PaymentService $payments,
    ): JsonResponse {
        $this->assertOwner($request, $payment);

        $result = $payments->verify($payment);

        return response()->json([
            'status' => $result->status,
        ]);
    }

    private function assertOwner(
        Request $request,
        Payment $payment,
    ): void {
        abort_unless(
            (int) $payment->order->user_id === (int) $request->user()->user_id,
            404,
        );
    }
}
