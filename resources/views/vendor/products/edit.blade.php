@extends('layouts.app')

@section('title', 'Edit Product Listing (UC-31) | ' . $product->name)

@section('head')
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/icons.css">
    <link rel="stylesheet" href="/css/company_footer.css">
    <link rel="stylesheet" href="/css/components/dropdown.css">
    <link rel="stylesheet" href="/css/components/persistent_header.css">
@endsection

@section('tail')
    <script src="/js/components/dropdown.js"></script>
    <script src="/js/components/persistent_header.js"></script>
    <script src="/js/components/side_panel.js"></script>
@endsection

@section('content')
    <section class="top-banner">
        <svg class="logo-larpshop-horizontal">
            <use href="/images/logo.svg#larpshop-horizontal"></use>
        </svg>
    </section>

    <header class="container sticky-top" style="background-color: #ffffff99;">
        <link rel="stylesheet" href="/css/top_header.css">
        <link rel="stylesheet" href="/css/components/navigation_bar.css">
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
                <li class="breadcrumb-item active" aria-current="page">Edit: {{ $product->name }}</li>
            </ol>
        </nav>

        <x-vendor.store-switcher :vendor="$vendor" :owned-vendors="$ownedVendors ?? collect()" />

        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 border-bottom pb-3 mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1">
                        <i class="bi bi-pencil-square text-primary me-2"></i>Edit Product Listing (UC-31)
                    </h3>
                    <p class="text-muted small mb-0">
                        Update details, stock quantity, and catalog visibility for <strong>{{ $product->name }}</strong> (#{{ $product->product_id }}).
                    </p>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="{{ route('vendor.products.images.index', $product->product_id) }}" class="btn btn-outline-primary rounded-pill px-3">
                        <i class="bi bi-images me-1"></i>Manage Images ({{ $product->images->count() }})
                    </a>
                    <form action="{{ route('vendor.products.toggle-active', $product->product_id) }}" method="POST" class="m-0">
                        @csrf
                        @method('PATCH')
                        @if ($product->is_active)
                            <button type="submit" class="btn btn-outline-warning rounded-pill px-3">
                                <i class="bi bi-eye-slash me-1"></i>Hide from Catalog
                            </button>
                        @else
                            <button type="submit" class="btn btn-outline-success rounded-pill px-3">
                                <i class="bi bi-eye me-1"></i>Make Visible
                            </button>
                        @endif
                    </form>
                </div>
            </div>

            <x-vendor.product-form
                :product="$product"
                :categories="$categories"
                :action="route('vendor.products.update', $product->product_id)"
                method="PUT"
                submit-label="Update Product Listing"
            />
        </div>
    </main>

    <footer class="mt-5">
        <x-company-footer />
    </footer>
@endsection
