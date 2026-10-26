@props(['order', 'canPay' => false])

@php
    $methods = [
        'simulated' => ['label' => 'Simulated payment', 'component' => 'checkout.payment-methods.simulated'],
        'card' => ['label' => 'Credit or Debit card', 'component' => 'checkout.payment-methods.card'],
        'wallet' => ['label' => 'Digital wallet', 'component' => 'checkout.payment-methods.wallet'],
    ];
@endphp

<section
    class="payment-method-container"
    aria-label="Payment method"
    data-payment-methods
>
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
                :can-pay="$canPay"
            />
        </div>
    @endforeach
</section>
