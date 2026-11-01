@extends('layouts.app')

@section('title', __('products.search.title'))

@section('head')

    @vite([
        'resources/css/icons.css',
        'resources/css/components/dropdown.css',
        'resources/css/components/product_card.css',
    ])
@endsection

@section('tail')
    @vite([
        'resources/js/components/product_quantity_picker.js',
        'resources/js/components/dropdown.js',
        'resources/js/components/side_panel.js',
    ])
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
    $hasActiveFilters = $filters['category'] !== ''
        || ($filters['min_price'] !== null && $filters['min_price'] !== '')
        || ($filters['max_price'] !== null && $filters['max_price'] !== '')
        || !empty($filters['in_stock'])
        || ($filters['sort'] ?? 'newest') !== 'newest';
@endphp
<div class="bg-1 min-vh-100">

    <header class="container sticky-top"
            style="background-color: #ffffffee;">

        @vite([
            'resources/css/top_header.css',
            'resources/css/components/navigation_bar.css',
        ])

        <x-nav-bar
            product-cart-bs-target="#side-panel"
            product-cart-bs-toggle="drawer"
            :product-categories="$productCategories"
            :cart="$cart"
        />
        <div id="dropdown-portal"></div>
    </header>

    <main class="container py-5">

        <div class="mb-4">
            <h1 class="fw-bold">
                {{ $keyword ? __('products.search.results') : __('products.search.explore') }}
            </h1>

            @if ($keyword)
                <p class="fg-secondary">
                    {{ __('products.search.results_for') }}
                    <strong>{{ $keyword }}</strong>
                </p>

                <a href="{{ route('products.index') }}"
                   class="btn btn-outline theme-secondary btn-sm">
                    {{ __('products.search.clear') }}
                </a>
            @else
                <p class="fg-secondary">
                    {{ __('products.search.intro') }}
                </p>
            @endif
        </div>

        @if ($errors->any())
            <div class="alert theme-danger rounded-5 shadow-sm mb-4" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-4">
            <button
                type="button"
                class="btn btn-outline theme-inverse side-panel-trigger"
                data-bs-toggle="drawer"
                data-bs-target="#side-panel"
                data-side-panel-target="#filter-panel-template"
            >
                <i class="bi bi-funnel"></i> {{ __('products.filters.title') }}
                @if ($hasActiveFilters)
                    <span class="badge bg-primary ms-1">{{ __('products.filters.active') }}</span>
                @endif
                <template id="filter-panel-template">
                    <x-products.filter-panel
                        :categories="$productCategories"
                        :filters="$filters"
                        :sort-options="$sortOptions"
                        :keyword="$keyword"
                        :has-active-filters="$hasActiveFilters"
                    />
                </template>
            </button>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h5 mb-0">
                {{ $keyword ? __('products.search.matching') : __('products.search.all') }}
            </h2>

            <span class="badge bg-primary">
                {{ trans_choice('products.search.found', $products->count(), ['count' => $products->count()]) }}
            </span>
        </div>

        @if ($products->isEmpty())

            <div class="bg-white rounded-9 shadow-sm text-center p-5">
                <i class="bi bi-search display-3 fg-secondary"></i>

                <h3 class="h4 mt-3">{{ __('products.search.empty') }}</h3>

                <p class="fg-secondary">
                    {{ __('products.search.try_again') }}
                </p>

                <a href="{{ route('products.index') }}"
                   class="btn btn-solid theme-primary">
                    {{ __('products.search.browse') }}
                </a>
            </div>

        @else

            <div class="row g-4">
                @foreach ($products as $product)
                    <div class="col-12 sm:col-6 lg:col-4 xl:col-3">
                        <x-product-card-b
                            :product="$product"
                            class="h-100"
                        />
                    </div>
                @endforeach
            </div>

        @endif

    </main>
        <x-side-panel alignment="end"/>
</div>
@endsection
