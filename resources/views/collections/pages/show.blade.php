@extends('layouts.app')

@section('title', $collection->name)

@section('head')
    @vite([
        'resources/css/icons.css',
        'resources/css/company_footer.css',
        'resources/css/top_header.css',
        'resources/css/components/navigation_bar.css',
        'resources/css/components/dropdown.css',
        'resources/css/components/persistent_header.css',
        'resources/css/components/product_card.css',
    ])
@endsection

@section('tail')
    @vite([
        'resources/js/components/dropdown.js',
        'resources/js/components/persistent_header.js',
        'resources/js/components/side_panel.js',
        'resources/js/components/product_quantity_picker.js',
    ])
@endsection

@section('content')
    <header class="container sticky-top bg-body">
        <x-nav-bar
            product-cart-bs-target="#side-panel"
            product-cart-bs-toggle="drawer"
            :product-categories="$productCategories"
            :cart="$cart"
        />
        <x-persistent-header />
        <div id="dropdown-portal"></div>
    </header>
    <main class="container py-4">
        {{ Breadcrumbs::render('collection', $collection) }}
        <header class="my-4">
            <h1 class="fw-semibold">{{ $collection->name }}</h1>
            @if ($collection->description)
                <p class="fg-3">{{ $collection->description }}</p>
            @endif
        </header>
        @if ($products->isEmpty())
            <p class="border rounded-2 p-4">{{ __('promotions.empty_collection') }}</p>
        @else
            <div class="row g-4 mb-4">
                @foreach ($products as $product)
                    <div class="col-6 md:col-4 xl:col-3">
                        <x-product-card-b :product="$product" class="h-100" />
                    </div>
                @endforeach
            </div>
            {{ $products->links() }}
        @endif
    </main>
    <x-side-panel alignment="end" />
    <x-company-footer />
@endsection
