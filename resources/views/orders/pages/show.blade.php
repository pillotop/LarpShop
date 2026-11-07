@extends('layouts.app')

@section('title', __('orders.details'))

@section('content')
    <main class="container py-5">
        <h1>{{ __('orders.number', ['id' => $order->getKey()]) }}</h1>
        @if (session('status'))
            <p role="status">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <p role="alert">{{ $errors->first() }}</p>
        @endif
        @if ($order->status === \App\Enums\OrderStatus::Paid)
            <p class="alert theme-success">{{ __('orders.confirmed') }}</p>
        @endif
        <p>{{ __('orders.status', ['status' => __('checkout.order_status.' . $order->status->value)]) }}</p>
        <x-checkout.order-summary :data="$order" />
        @foreach ($order->payments as $payment)
            <p>{{ __('orders.payment', ['id' => $payment->getKey(), 'provider' => $payment->provider, 'status' => __('checkout.payment_status.' . $payment->status->value)]) }}</p>
        @endforeach
        <a href="{{ route('checkout.show', $order) }}">{{ __('orders.shipping_payment') }}</a>
        <a href="{{ route('orders.index') }}">{{ __('orders.history') }}</a>
        @if ($order->status === \App\Enums\OrderStatus::Pending)
            <form
                action="{{ route('orders.cancel', $order) }}"
                method="POST"
                class="mt-3"
            >
                @csrf
                <button
                    type="submit"
                    class="btn btn-outline theme-danger"
                >
                    {{ __('orders.cancel') }}
                </button>
            </form>
        @endif
    </main>
@endsection
