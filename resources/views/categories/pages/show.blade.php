@extends('layouts.app')

@section('title', $category->name)

@section('head')
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/icons.css">
    <link rel="stylesheet" href="/css/company_footer.css">
    <link rel="stylesheet" href="/css/components/dropdown.css">
    <link rel="stylesheet" href="/css/components/persistent_header.css">
    <link rel="stylesheet" href="/css/components/two_arrow_scroller.css">
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
            :product-categories="$productCategories"
            :cart="$cart"
        />
        <div class="persistent-header position-relative mt-2 px-4 py-2 z-0">
            <ul class="persistent-header-content d-flex align-items-center gap-3 p-0 overflow-scroll" style="list-style: none;">
                <li>
                    <button class="nav-button rounded-pill border-0" data-bs-target="#side-panel" data-bs-toggle="offcanvas">
                        <svg class="icon-16">
                            <use href="/images/icons.svg#settings-sliders"></use>
                        </svg>
                        <span> Settings </span>
                        <template id="settings">
                        </template>
                    </button>
                </li>
                <li>
                    <button type="button" class="nav-button rounded-pill border-0">
                        Parcel delivery
                    </button>
                </li>
                <li>
                    <button type="button" class="nav-button rounded-pill border-0">
                        Phone sales service
                    </button>
                </li>
                <li>
                    <button type="button" class="nav-button rounded-pill border-0">
                        Return policy
                    </button>
                </li>
            </ul>
        </div>
        <div id="dropdown-portal"></div>
    </header>
    <main>
        <section class="container"> {{ Breadcrumbs::render('category', $category) }}</section>
        <section id="subcategories" class="container">
            <h1>{{ $category->name }}</h1>
            @if ($category->children->isNotEmpty())
                <div class="row">
                    <div class="col-4 d-flex flex-column overflow-scroll border-end-dashed border-top-0 border-thin border-start-0 border-bottom-0">
                    </div>
                    <div class="col-6 border-bottom-dashed border-thin border-top-0 border-start-0 border-end-0">
                        <h5 class="fw-bold"> Categories </h5>
                    </div>
                </div>
                <div class="row">
                    <div class="subcategory-container col-4 p-0 overflow-scroll border-end-dashed border-top-dashed border-thin border-start-0 border-bottom-0">
                        <ul id="subcategory-list" class="d-flex flex-column gap-2" style="list-style: none;">
                            @foreach ($category->children as $childCategory)
                                <li>
                                    <a href="{{ route('categories.show', ['slug' => $childCategory->slug]) }}">
                                        <button class="subcategory-button w-100" type="button" data-subcategory-container-anchor-id="{{ $loop->index }}">
                                            {{ $childCategory->name }}
                                        </button>
                                        <template id="{{ $loop->index }}">
                                            <div class="subcategory-banner">
                                                <div class="ratio ratio-16x9">
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
                    <div class="col-6 p-4" data-subcategory-container-injection-point>
                    </div>
                </div>
            @endif
        </section>
        <section class="container">
            @if ($products->isNotEmpty())
                <div class="d-flex justify-content-end border border-4 border-top-0 border-start-0 border-end-0 my-3" style="border-color: var(--color-tertiary-500) !important;">
                    <h2 class="fw-bold p-3 m-0" style="color: white; background-color: var(--color-tertiary-500);"> Products of the same type </h2>
                </div>
                <x-two-arrow-scroller>
                    <ul class="d-flex gap-4 overflow-scroll list-unstyled">
                        @foreach ($products as $product)
                            <li>
                                <x-product-card-b :product="$product"/>
                            </li>
                        @endforeach
                    </ul>
                </x-two-arrow-scroller>
            @else
                <div class="d-flex flex-column justify-content-center align-items-center text-center py-5" style="min-height: 350px;">
                    <i class="bi bi-box-seam display-4 text-secondary mb-3"></i>
                    <h3 class="h4 fw-bold text-dark">No products found in this category</h3>
                    <p class="text-muted">Check back later or explore other categories.</p>
                    <a href="{{ route('home') }}" class="btn btn-outline-primary mt-2">Back to Home</a>
                </div>
            @endif
        </section>
    </main>
    <x-side-panel/>
    <x-modal/>
    <x-company-footer/>
@endsection

@section('tail')
    <script src="/js/components/side_panel.js"></script>
    <script src="/js/components/product_quantity_picker.js"></script>
    <script src="/js/components/two_arrow_scroller.js"></script>
    <script src="/js/components/subcategory_container.js"></script>
    <script src="/js/components/carousel_scrollable.js"></script>
    <script src="/js/components/dropdown.js"></script>
    <script src="/js/components/persistent_header.js"></script>
@endsection
