@extends('layouts.app')

@section('title', 'Search Products - LarpShop')

@section('head')
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/icons.css">
    <link rel="stylesheet" href="/css/components/dropdown.css">
    <link rel="stylesheet" href="/css/components/product_card.css">
@endsection

@section('tail')
    <script src="/js/components/dropdown.js"></script>
    <script src="/js/components/side_panel.js"></script>
@endsection

@section('content')
@php
    $filters = $filters ?? [
        'category' => '',
        'min_price' => null,
        'max_price' => null,
        'in_stock' => false,
        'sort' => 'newest',
    ];
    $sortOptions = $sortOptions ?? [
        'newest' => 'Newest Arrivals',
        'price_asc' => 'Price: Low to High',
        'price_desc' => 'Price: High to Low',
        'name_asc' => 'Name: A to Z',
        'name_desc' => 'Name: Z to A',
        'rating_desc' => 'Highest Rated',
    ];
    $hasActiveFilters = $filters['category'] !== ''
        || ($filters['min_price'] !== null && $filters['min_price'] !== '')
        || ($filters['max_price'] !== null && $filters['max_price'] !== '')
        || !empty($filters['in_stock'])
        || ($filters['sort'] ?? 'newest') !== 'newest';
@endphp
<div class="bg-light min-vh-100">

    <header class="container sticky-top"
            style="background-color: #ffffffee;">

        <link rel="stylesheet" href="/css/top_header.css">
        <link rel="stylesheet" href="/css/components/navigation_bar.css">

        <x-nav-bar
            product-cart-bs-target="#side-panel"
            product-cart-bs-toggle="offcanvas"
            :product-categories="$productCategories"
            :cart="$cart"
        />
    </header>

    <main class="container py-5">

        <div class="mb-4">
            <h1 class="fw-bold">
                {{ $keyword ? 'Search Results' : 'Explore Products' }}
            </h1>

            @if ($keyword)
                <p class="text-secondary">
                    Results for:
                    <strong>{{ $keyword }}</strong>
                </p>

                <a href="{{ route('products.index') }}"
                   class="btn btn-outline-secondary btn-sm">
                    Clear Search
                </a>
            @else
                <p class="text-secondary">
                    Discover products that match your needs.
                </p>
            @endif
        </div>

        @if ($errors->any())
            <div class="alert alert-danger rounded-3 shadow-sm mb-4" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section aria-label="Product filters and sorting" class="bg-white rounded-4 shadow-sm p-4 mb-4">
            <form method="GET" action="{{ route('products.index') }}" class="row g-3 align-items-end">
                @if ($keyword !== '')
                    <input type="hidden" name="q" value="{{ $keyword }}">
                @endif

                <div class="col-12 col-md-3">
                    <label for="filter-category" class="form-label small fw-semibold text-secondary mb-1">
                        Category
                    </label>
                    <select id="filter-category" name="category" class="form-select form-select-sm">
                        <option value="">All Categories</option>
                        @foreach ($productCategories as $categoryOption)
                            <option
                                value="{{ $categoryOption->slug }}"
                                @selected($filters['category'] === $categoryOption->slug || $filters['category'] === (string) $categoryOption->category_id)
                            >
                                {{ $categoryOption->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label for="filter-min-price" class="form-label small fw-semibold text-secondary mb-1">
                        Min Price ($)
                    </label>
                    <input
                        id="filter-min-price"
                        type="number"
                        name="min_price"
                        min="0"
                        step="0.01"
                        placeholder="0.00"
                        value="{{ $filters['min_price'] ?? '' }}"
                        class="form-control form-control-sm"
                    >
                </div>

                <div class="col-6 col-md-2">
                    <label for="filter-max-price" class="form-label small fw-semibold text-secondary mb-1">
                        Max Price ($)
                    </label>
                    <input
                        id="filter-max-price"
                        type="number"
                        name="max_price"
                        min="0"
                        step="0.01"
                        placeholder="Any"
                        value="{{ $filters['max_price'] ?? '' }}"
                        class="form-control form-control-sm"
                    >
                </div>

                <div class="col-12 col-md-3">
                    <label for="filter-sort" class="form-label small fw-semibold text-secondary mb-1">
                        Sort By
                    </label>
                    <select id="filter-sort" name="sort" class="form-select form-select-sm">
                        @foreach ($sortOptions as $sortKey => $sortLabel)
                            <option value="{{ $sortKey }}" @selected(($filters['sort'] ?? 'newest') === $sortKey)>
                                {{ $sortLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-2 d-flex flex-column justify-content-end">
                    <div class="form-check mb-2">
                        <input
                            id="filter-in-stock"
                            type="checkbox"
                            name="in_stock"
                            value="1"
                            class="form-check-input"
                            @checked(!empty($filters['in_stock']))
                        >
                        <label for="filter-in-stock" class="form-check-label small">
                            In Stock Only
                        </label>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            Apply Filters
                        </button>
                        @if ($hasActiveFilters)
                            <a
                                href="{{ route('products.index', $keyword !== '' ? ['q' => $keyword] : []) }}"
                                class="btn btn-outline-secondary btn-sm"
                            >
                                Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </section>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h5 mb-0">
                {{ $keyword ? 'Matching Products' : 'All Products' }}
            </h2>

            <span class="badge bg-primary">
                {{ $products->count() }}
                {{ $products->count() === 1 ? 'product found' : 'products found' }}
            </span>
        </div>

        @if ($products->isEmpty())

            <div class="bg-white rounded-4 shadow-sm text-center p-5">
                <i class="bi bi-search display-3 text-secondary"></i>

                <h3 class="h4 mt-3">No products found</h3>

                <p class="text-secondary">
                    Try another keyword or browse all products.
                </p>

                <a href="{{ route('products.index') }}"
                   class="btn btn-primary">
                    Browse All Products
                </a>
            </div>

        @else

            <div class="row g-4">
                @foreach ($products as $product)
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <x-product-card-b
                            :product="$product"
                            class="h-100"
                        />
                    </div>
                @endforeach
            </div>

        @endif

    </main>
</div>
@endsection
