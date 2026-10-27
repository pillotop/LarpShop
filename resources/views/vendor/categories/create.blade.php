@extends('layouts.app')

@section('title', 'Create Category | ' . $vendor->store_name)

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
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('vendor.categories.index') }}">Categories</a></li>
                <li class="breadcrumb-item active" aria-current="page">Create Category</li>
            </ol>
        </nav>

        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
            <div class="border-bottom pb-3 mb-4">
                <h3 class="fw-bold text-dark mb-1">
                    <i class="bi bi-folder-plus text-primary me-2"></i>Create Product Category
                </h3>
                <p class="text-muted small mb-0">Add a new category to organize products across the marketplace.</p>
            </div>

            <x-vendor.category-form
                :parent-categories="$parentCategories"
                :action="route('vendor.categories.store')"
                method="POST"
                submit-label="Create Category"
            />
        </div>
    </main>

    <footer class="mt-5">
        <x-company-footer />
    </footer>
@endsection
