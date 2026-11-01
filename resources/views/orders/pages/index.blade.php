@extends('layouts.app')

@section('title', __('orders.history'))

@section('content')
    <main class="container py-5">
        <h1>{{ __('orders.history') }}</h1>
        <x-review-feedback />
        @forelse ($orders as $order)
            <article class="border rounded p-3 mb-3">
                <a href="{{ route('orders.show', $order) }}">{{ __('orders.number', ['id' => $order->getKey()]) }}</a>
                <p>{{ $order->created_at->format('Y-m-d H:i') }} — {{ __('checkout.order_status.' . $order->status->value) }}</p>
                <p>{{ __('orders.total', ['amount' => $order->total_amount]) }}</p>
                <x-orders.review-products
                    :order="$order"
                    :reviewable-product-ids="$reviewableProductIds"
                />
            </article>
        @empty
            <p>{{ __('orders.empty') }}</p>
        @endforelse
        {{ $orders->links() }}
    </main>
@endsection
