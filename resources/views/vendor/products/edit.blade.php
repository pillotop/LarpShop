@extends('layouts.app')

@section('title', __('management.edit_product_listing').' (UC-31) | ' . $product->name)

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
            product-cart-bs-toggle="drawer"
            :product-categories="$productCategories ?? []"
            :cart="$cart"
        />
        <div id="dropdown-portal"></div>
    </header>

    <main class="container my-4">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('management.home') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('vendor.products.index') }}">{{ __('management.products_inventory') }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ __('management.display.edit') }} {{ $product->name }}</li>
            </ol>
        </nav>

        <x-vendor.store-switcher :vendor="$vendor" :owned-vendors="$ownedVendors ?? collect()" />

        <div class="card border-0 shadow-sm rounded-9 p-4 md:p-5 bg-white mb-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 border-bottom pb-3 mb-4">
                <div>
                    <h3 class="fw-bold fg-body mb-1">
                        <i class="bi bi-pencil-square fg-primary me-2"></i>{{ __('management.edit_product_listing_uc_31') }}
                    </h3>
                    <p class="fg-3 small mb-0">
                        {{ __('management.update_details_stock_quantity_and_catalog_visibility_for') }} <strong>{{ $product->name }}</strong> (#{{ $product->product_id }}).
                    </p>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="{{ route('vendor.products.images.index', $product->product_id) }}" class="btn btn-outline theme-primary rounded-pill px-3">
                        <i class="bi bi-images me-1"></i>Manage Images ({{ $product->images->count() }})
                    </a>
                    <form action="{{ route('vendor.products.toggle-active', $product->product_id) }}" method="POST" class="m-0">
                        @csrf
                        @method('PATCH')
                        @if ($product->is_active)
                            <button type="submit" class="btn btn-outline theme-warning rounded-pill px-3">
                                <i class="bi bi-eye-slash me-1"></i>{{ __('management.hide_from_catalog') }}
                            </button>
                        @else
                            <button type="submit" class="btn btn-outline theme-success rounded-pill px-3">
                                <i class="bi bi-eye me-1"></i>{{ __('management.make_visible') }}
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
                submit-label="{{ __('management.update_product_listing') }}"
            />
        </div>
    </main>

    <footer class="mt-5">
        <x-company-footer />
    </footer>
@endsection
