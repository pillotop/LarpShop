@extends('layouts.app')

@section('title', 'Order details')

@section('content')
    <main class="container py-5">
        <h1>Order #{{ $order->getKey() }}</h1>
        @if (session('status'))
            <p role="status">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <p role="alert">{{ $errors->first() }}</p>
        @endif
        @if ($order->status === \App\Enums\OrderStatus::Paid)
            <p class="alert alert-success">Your order is confirmed. Payment received.</p>
        @endif
        <p>Order status: {{ ucfirst($order->status->value) }}</p>
        <x-checkout.order-summary :data="$order" />
        @foreach ($order->payments as $payment)
            <p>Payment #{{ $payment->getKey() }}: {{ $payment->provider }} — {{ $payment->status->value }}</p>
        @endforeach
        <a href="{{ route('checkout.show', $order) }}">View payment and shipping details</a>
        <a href="{{ route('orders.index') }}">Order history</a>
        @if ($order->status === \App\Enums\OrderStatus::Pending)
            <form
                action="{{ route('orders.cancel', $order) }}"
                method="POST"
                class="mt-3"
            >
                @csrf
                <button
                    type="submit"
                    class="btn btn-outline-danger"
                >
                    Cancel order
                </button>
            </form>
        @endif
    </main>
@endsection
