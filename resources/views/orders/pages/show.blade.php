@extends('layouts.checkout')

@section('title', __('orders.details'))

@section('payment-content')
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h2 mb-2">{{ __('orders.number', ['id' => $order->getKey()]) }}</h1>
            <p class="fg-3 mb-0">{{ __('orders.status', ['status' => __('checkout.order_status.' . $order->status->value)]) }}</p>
            <time class="small fg-3" datetime="{{ $order->created_at->toIso8601String() }}">{{ $order->created_at->translatedFormat('d M Y, H:i') }}</time>
        </div>
        <a class="btn btn-outline theme-primary" href="{{ route('checkout.show', $order) }}">{{ __('orders.shipping_payment') }}</a>
    </div>
    @if ($order->status === \App\Enums\OrderStatus::Paid)
        <p class="alert theme-primary" role="status">{{ __('orders.confirmed') }}</p>
    @endif
    <div class="payment-columns">
        <div class="d-grid gap-4">
            <section class="payment-surface p-3 md:p-4" aria-labelledby="order-shipping-title">
                <h2 id="order-shipping-title" class="h4 mb-3">{{ __('checkout.address.title') }}</h2>
                <x-checkout.shipping-address :address="$order->addresses->firstWhere('type', 'shipping')" />
            </section>
            <section class="payment-surface p-3 md:p-4" aria-labelledby="order-payments-title">
                <h2 id="order-payments-title" class="h4 mb-3">{{ __('orders.payment_attempts') }}</h2>
                @forelse ($order->payments->sortByDesc('payment_id') as $payment)
                    <article class="payment-record py-3">
                        <p class="fw-semibold mb-2">{{ __('orders.payment', ['id' => $payment->getKey(), 'provider' => config("payments.providers.{$payment->provider}.label", $payment->provider), 'status' => __('checkout.payment_status.' . $payment->status->value)]) }}</p>
                        <p class="mb-1">{{ $payment->currency }} {{ $payment->amount }}</p>
                        <time class="small fg-3" datetime="{{ $payment->created_at->toIso8601String() }}">{{ $payment->created_at->translatedFormat('d M Y, H:i') }}</time>
                        @if ($payment->status === \App\Enums\PaymentStatus::RefundRequired)
                            <p class="mt-2 mb-0" role="status">{{ __('checkout.payment.refund_review') }}</p>
                        @endif
                    </article>
                @empty
                    <p class="fg-3 mb-0">{{ __('orders.no_payments') }}</p>
                @endforelse
            </section>
            @if ($order->status === \App\Enums\OrderStatus::Pending)
                <form action="{{ route('orders.cancel', $order) }}" method="POST" onsubmit="return confirm(@js(__('profile.purchases.cancel_confirm')))">
                    @csrf
                    <button type="submit" class="btn btn-outline theme-secondary">{{ __('orders.cancel') }}</button>
                </form>
            @endif
        </div>
        <aside class="payment-summary"><x-checkout.order-summary :data="$order" /></aside>
    </div>
@endsection
