@extends('layouts.app')

@section('title', 'Create Product Listing (UC-30) | ' . $vendor->store_name)

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
    <section class="top-banner">
        <svg class="logo-larpshop-horizontal">
            <use href="/images/logo.svg#larpshop-horizontal"></use>
        </svg>
    </section>

    <header class="container sticky-top" style="background-color: #ffffff99;">
        @vite([
            'resources/css/top_header.css',
            'resources/css/components/navigation_bar.css',
        ])
        <x-nav-bar
            product-cart-bs-target="#side-panel"
            product-cart-bs-toggle="offcanvas"
            :product-categories="$productCategories ?? []"
            :cart="$cart"
        />
        <div id="dropdown-portal"></div>
    </header>

    <main class="container my-4">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('vendor.products.index') }}">Products & Inventory</a></li>
                <li class="breadcrumb-item active" aria-current="page">Create Product Listing</li>
            </ol>
        </nav>

        <x-vendor.store-switcher :vendor="$vendor" :owned-vendors="$ownedVendors ?? collect()" />

        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
            <div class="border-bottom pb-3 mb-4">
                <h3 class="fw-bold text-dark mb-1">
                    <i class="bi bi-plus-circle text-primary me-2"></i>Create New Product Listing (UC-30)
                </h3>
                <p class="text-muted small mb-0">
                    Publish a new item for <strong>{{ $vendor->store_name }}</strong> with pricing, stock quantity, and optional images.
                </p>
            </div>

            <x-vendor.product-form
                :categories="$categories"
                :action="route('vendor.products.store')"
                method="POST"
                submit-label="Create Product Listing"
                :allow-image-upload="true"
            />
        </div>
    </main>

    <footer class="mt-5">
        <x-company-footer />
    </footer>
@endsection
