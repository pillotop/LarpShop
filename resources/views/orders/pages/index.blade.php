@extends('layouts.app')

@section('title', __('orders.history'))

@section('head')
    @vite([
        'resources/css/icons.css',
        'resources/css/company_footer.css',
        'resources/css/components/dropdown.css',
        'resources/css/components/persistent_header.css',
    ])
@endsection

@section('tail')
    @vite([
        'resources/js/components/dropdown.js',
        'resources/js/components/persistent_header.js',
        'resources/js/components/side_panel.js',
    ])
@endsection

@section('content')
    <header class="container sticky-top" style="background-color: #ffffff99;">
        @vite([
            'resources/css/top_header.css',
            'resources/css/components/navigation_bar.css',
        ])
        <x-nav-bar
            product-cart-bs-target="#side-panel"
            product-cart-bs-toggle="drawer"
            :product-categories="$productCategories ?? []"
            :cart="$cart"
        />
        <div id="dropdown-portal"></div>
    </header>

    <main class="container py-5">
        <h1 class="h3 fw-bold mb-4">{{ __('orders.history') }}</h1>
        <x-review-feedback />
        @forelse ($orders as $order)
            <article class="card border-0 shadow-sm rounded-9 p-4 mb-3 bg-white">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom pb-2 mb-3">
                    <a href="{{ route('orders.show', $order) }}" class="fw-bold text-decoration-none">{{ __('orders.number', ['id' => $order->getKey()]) }}</a>
                    <span class="badge bg-1 fg-body border rounded-pill">{{ $order->created_at->format('Y-m-d H:i') }} — {{ __('checkout.order_status.' . $order->status->value) }}</span>
                </div>
                <p class="fw-semibold mb-2">{{ __('orders.total', ['amount' => $order->total_amount]) }}</p>
                <x-orders.review-products
                    :order="$order"
                    :reviewable-product-ids="$reviewableProductIds"
                />
            </article>
        @empty
            <div class="card border-0 shadow-sm rounded-9 p-5 text-center bg-white">
                <p class="fg-3 mb-0">{{ __('orders.empty') }}</p>
            </div>
        @endforelse
        <div class="mt-4">{{ $orders->links() }}</div>
    </main>

    <x-company-footer />
    <x-side-panel />
@endsection
