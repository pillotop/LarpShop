@extends('layouts.app')

@section('title', 'Order history')

@section('content')
    <main class="container py-5">
        <h1>Order history</h1>
        <x-review-feedback />
        @forelse ($orders as $order)
            <article class="border rounded p-3 mb-3">
                <a href="{{ route('orders.show', $order) }}">Order #{{ $order->getKey() }}</a>
                <p>{{ $order->created_at->format('Y-m-d H:i') }} — {{ ucfirst($order->status->value) }}</p>
                <p>Total: {{ $order->total_amount }}</p>
                <x-orders.review-products
                    :order="$order"
                    :reviewable-product-ids="$reviewableProductIds"
                />
            </article>
        @empty
            <p>You have no orders yet.</p>
        @endforelse
        {{ $orders->links() }}
    </main>
@endsection
