@props([
    'order',
    'activePayment' => null,
    'canCreatePayment' => false,
    'canResumePayment' => false,
])

@php
    $methods = [
        'simulated' => ['label' => __('checkout.payment.simulated'), 'component' => 'checkout.payment-methods.simulated'],
        'card' => ['label' => __('checkout.payment.card'), 'component' => 'checkout.payment-methods.card'],
        'wallet' => ['label' => __('checkout.payment.wallet'), 'component' => 'checkout.payment-methods.wallet'],
    ];
    if (! Route::has('test.payments.pay')) {
        unset($methods['simulated']);
    }
@endphp

<section
    class="payment-method-container"
    aria-label="{{ __('checkout.payment.method') }}"
    data-payment-methods
>
    @if ($canCreatePayment)
        <div
            id="method-headers"
            class="method-headers d-flex overflow-auto rounded-top-3 mb-4"
            role="tablist"
            aria-label="{{ __('checkout.payment.method') }}"
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
        <h2 class="h4 fw-bold mb-3">{{ __('checkout.payment.continue') }}</h2>

        <p class="fg-3">
            {{ __('checkout.payment.ready') }}
        </p>

        <a
            href="{{ $activePayment->checkout_url }}"
            class="btn btn-solid theme-primary w-100 py-3"
        >
            {{ __('checkout.payment.continue') }}
        </a>
    @else
        @php
            $message = match (true) {
                $activePayment?->status === \App\Enums\PaymentStatus::RefundRequired => __('checkout.payment.refund_review'),
                $order->status === \App\Enums\OrderStatus::Paid => __('checkout.payment.paid'),
                $activePayment?->status === \App\Enums\PaymentStatus::Processing => __('checkout.payment.processing'),
                $activePayment?->status === \App\Enums\PaymentStatus::Pending => __('checkout.payment.pending'),
                default => __('checkout.payment.unavailable'),
            };
        @endphp

        <h2 class="h4 fw-bold mb-3">{{ __('checkout.payment.status') }}</h2>

        <p
            class="fg-3"
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
        {{ __('checkout.payment.view_order') }}
    </a>
</section>
