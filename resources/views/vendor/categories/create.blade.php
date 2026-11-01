@extends('layouts.app')

@section('title', __('management.create_category').' | ' . $vendor->store_name)

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
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('management.home') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('vendor.categories.index') }}">{{ __('management.categories') }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ __('management.create_category') }}</li>
            </ol>
        </nav>

        <div class="card border-0 shadow-sm rounded-9 p-4 md:p-5 bg-white mb-4">
            <div class="border-bottom pb-3 mb-4">
                <h3 class="fw-bold fg-body mb-1">
                    <i class="bi bi-folder-plus fg-primary me-2"></i>{{ __('management.create_product_category') }}
                </h3>
                <p class="fg-3 small mb-0">{{ __('management.add_a_new_category_to_organize_products_across_the_marketplace') }}</p>
            </div>

            <x-vendor.category-form
                :parent-categories="$parentCategories"
                :action="route('vendor.categories.store')"
                method="POST"
                submit-label="{{ __('management.create_category') }}"
            />
        </div>
    </main>

    <footer class="mt-5">
        <x-company-footer />
    </footer>
@endsection
