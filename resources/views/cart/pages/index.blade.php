@extends('layouts.app')

@section('title', __('cart.title'))

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
        'resources/js/components/product_quantity_picker.js',
    ])
@endsection

@section('content')
    <header class="container sticky-top" style="background-color: #ffffff99;">
        @vite([
            'resources/css/top_header.css',
            'resources/css/components/navigation_bar.css',
        ])
        <x-nav-bar product-cart-bs-target="#side-panel" product-cart-bs-toggle="drawer" :product-categories="$productCategories" :cart="$cart" />
        <x-persistent-header />
        <div id="dropdown-portal"></div>
    </header>
    <main>

        <div class="container" data-cart-page>
            @include('cart.partials.content', ['cart' => $cart])
        </div>
    </main>
    <x-company-footer />
    <x-side-panel />
@endsection
