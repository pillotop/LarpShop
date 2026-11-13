@extends('layouts.app')

@section('title', $category->name)

@section('head')

    @vite([
        'resources/css/icons.css',
        'resources/css/company_footer.css',
        'resources/css/components/dropdown.css',
        'resources/css/components/persistent_header.css',
        'resources/css/components/two_arrow_scroller.css',
        'resources/css/components/product_card.css',
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
            :product-categories="$productCategories"
            :cart="$cart"
        />
        <x-persistent-header />
        <div id="dropdown-portal"></div>
    </header>
    <main>
        <section class="container"> {{ Breadcrumbs::render('category', $category) }}</section>
        <section id="subcategories" class="container">
            <h1>{{ $category->name }}</h1>
            @if ($category->children->isNotEmpty())
                <div class="row">
                    <div class="col-12 md:col-4 d-none md:d-flex flex-column overflow-auto border-end-dashed border-top-0 border-thin border-start-0 border-bottom-0">
                    </div>
                    <div class="col-12 md:col-8 border-bottom-dashed border-thin border-top-0 border-start-0 border-end-0">
                        <h5 class="fw-bold">{{ __('common.categories') }}</h5>
                    </div>
                </div>
                <div class="row">
                    <div class="subcategory-container col-12 md:col-4 p-0 overflow-auto border-end-dashed border-top-dashed border-thin border-start-0 border-bottom-0">
                        <ul id="subcategory-list" class="d-flex flex-column gap-2" style="list-style: none;">
                            @foreach ($category->children as $childCategory)
                                <li>
                                    <a href="{{ route('categories.show', ['slug' => $childCategory->slug]) }}">
                                        <button class="subcategory-button w-100" type="button" data-subcategory-container-anchor-id="{{ $loop->index }}">
                                            {{ $childCategory->name }}
                                        </button>
                                        <template id="{{ $loop->index }}">
                                            <div class="subcategory-banner">
                                                <div class="media-frame ratio-16x9">
                                                    <img
                                                        class="object-fit-cover"
                                                        {{-- src="{{ asset('images/' . $childCategory->images) }}" --}}
                                                        alt="{{ $childCategory->name }}" />
                                                </div>
                                            </div>
                                        </template>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="col-12 md:col-8 p-4" data-subcategory-container-injection-point>
                    </div>
                </div>
            @endif
        </section>
        <section class="container">
            @if ($products->isNotEmpty())
                <div class="d-flex justify-content-end border border-4 border-top-0 border-start-0 border-end-0 my-3" style="border-color: var(--color-tertiary-500) !important;">
                    <h2 class="fw-bold p-3 m-0" style="color: white; background-color: var(--color-tertiary-500);">{{ __('products.category.products') }}</h2>
                </div>
                <x-two-arrow-scroller>
                    <ul id="two-arrow-scrollable" class="d-flex gap-4 overflow-scroll list-unstyled">
                        @foreach ($products as $product)
                            <li>
                                <x-product-card-b :product="$product"/>
                            </li>
                        @endforeach
                    </ul>
                </x-two-arrow-scroller>
            @else
                <div class="d-flex flex-column justify-content-center align-items-center text-center py-5" style="min-height: 350px;">
                    <i class="bi bi-box-seam display-4 fg-secondary mb-3"></i>
                    <h3 class="h4 fw-bold fg-body">{{ __('products.category.empty') }}</h3>
                    <p class="fg-3">{{ __('products.category.explore') }}</p>
                    <a href="{{ route('home') }}" class="btn btn-outline theme-primary mt-2">{{ __('products.category.home') }}</a>
                </div>
            @endif
        </section>
    </main>
    <x-side-panel/>
    <x-modal/>
    <x-company-footer/>
@endsection

@section('tail')
    @vite([
        'resources/js/components/side_panel.js',
        'resources/js/components/product_quantity_picker.js',
        'resources/js/components/two_arrow_scroller.js',
        'resources/js/components/subcategory_container.js',
        'resources/js/components/carousel_scrollable.js',
        'resources/js/components/dropdown.js',
        'resources/js/components/persistent_header.js',
    ])
@endsection
