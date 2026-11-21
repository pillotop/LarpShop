<?php

namespace App\Http\Controllers\Payments;

use App\Enums\FakePaymentOutcome;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use App\Services\Payments\SimulatedWalletGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SimulatedWalletController extends Controller
{
    public function show(Request $request, Payment $payment): View|RedirectResponse
    {
        $this->assertAccessible($request, $payment);

        if ($payment->status !== PaymentStatus::Pending) {
            return redirect()->route('checkout.show', $payment->order_id);
        }

        return view('checkout.pages.wallet-demo', compact('payment'));
    }

    public function store(
        Request $request,
        Payment $payment,
        SimulatedWalletGateway $gateway,
        PaymentService $payments,
    ): RedirectResponse {
        $this->assertAccessible($request, $payment);
        $data = $request->validate([
            'outcome' => ['required', Rule::enum(FakePaymentOutcome::class)],
        ]);

        $gateway->simulate($payment->provider_reference, FakePaymentOutcome::from($data['outcome']));
        $result = $payments->complete($payment);

        return redirect()->route(
            $result->status === PaymentStatus::Succeeded->value ? 'orders.show' : 'checkout.show',
            $payment->order_id,
        )->with('status', __('checkout.demo.status_message', [
            'status' => __('checkout.payment_status.'.$result->status),
        ]));
    }

    private function assertAccessible(Request $request, Payment $payment): void
    {
        abort_unless(
            (int) $payment->order->user_id === (int) $request->user()->getKey()
            && config("payments.providers.{$payment->provider}.enabled", false)
            && config("payments.providers.{$payment->provider}.gateway") === SimulatedWalletGateway::class
            && $payment->provider_reference !== null,
            404,
        );
    }
}
