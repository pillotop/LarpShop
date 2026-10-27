@props([
    'order',
    'activePayment' => null,
    'canCreatePayment' => false,
    'canResumePayment' => false,
])

@php
    $methods = [
        'simulated' => ['label' => 'Simulated payment', 'component' => 'checkout.payment-methods.simulated'],
        'card' => ['label' => 'Credit or Debit card', 'component' => 'checkout.payment-methods.card'],
        'wallet' => ['label' => 'Digital wallet', 'component' => 'checkout.payment-methods.wallet'],
    ];
    if (! Route::has('test.payments.pay')) {
        unset($methods['simulated']);
    }
@endphp

<section
    class="payment-method-container"
    aria-label="Payment method"
    data-payment-methods
>
    @if ($canCreatePayment)
        <div
            id="method-headers"
            class="method-headers d-flex overflow-auto rounded-top-3 mb-4"
            role="tablist"
            aria-label="Payment method"
        >
            @foreach ($methods as $key => $method)
                <button
                    id="payment-tab-{{ $key }}"
                    type="button"
                    class="payment-method-tab flex-shrink-0 border border-bottom-0 rounded-top-3 py-2 px-3 {{ $loop->first ? 'active' : '' }}"
                    role="tab"
                    aria-controls="payment-panel-{{ $key }}"
                    aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                    tabindex="{{ $loop->first ? '0' : '-1' }}"
                >
                    {{ $method['label'] }}
                </button>
            @endforeach
        </div>

        @foreach ($methods as $key => $method)
            <div
                id="payment-panel-{{ $key }}"
                class="payment-method-panel"
                role="tabpanel"
                aria-labelledby="payment-tab-{{ $key }}"
                tabindex="0"
                @if (! $loop->first) hidden @endif
            >
                <x-dynamic-component
                    :component="$method['component']"
                    :order="$order"
                    :can-create-payment="$canCreatePayment"
                />
            </div>
        @endforeach
    @elseif ($canResumePayment && $activePayment !== null)
        <h2 class="h4 fw-bold mb-3">Continue payment</h2>

        <p class="text-muted">
            Your payment session is ready. Continue with your payment provider.
        </p>

        <a
            href="{{ $activePayment->checkout_url }}"
            class="btn btn-primary w-100 py-3"
        >
            Continue payment
        </a>
    @else
        @php
            $message = match (true) {
                $activePayment?->status === \App\Enums\PaymentStatus::RefundRequired => 'Your payment requires refund review.',
                $order->status === \App\Enums\OrderStatus::Paid => 'This order has been paid.',
                $activePayment?->status === \App\Enums\PaymentStatus::Processing => 'Your payment is being processed.',
                $activePayment?->status === \App\Enums\PaymentStatus::Pending => 'Your payment is pending. No continuation link is available.',
                default => 'Payment is unavailable. The order may be closed or need a shipping address.',
            };
        @endphp

        <h2 class="h4 fw-bold mb-3">Payment status</h2>

        <p
            class="text-muted"
            role="status"
        >
            {{ $message }}
        </p>
    @endif
    @if ($activePayment !== null)
        <x-checkout.payment-actions :payment="$activePayment" />
    @endif
    <a
        href="{{ route('orders.show', $order) }}"
        class="d-inline-block mt-3"
    >
        View order details
    </a>
</section>
