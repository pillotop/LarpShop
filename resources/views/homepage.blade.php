@extends('layouts.app')

@section('title', 'Larp Shop | Larp away your worries')

@section('head')

    @vite([
        'resources/css/icons.css',
        'resources/css/category_banner.css',
        'resources/css/company_footer.css',
        'resources/css/broad_select.css',
        'resources/css/components/two_arrow_scroller.css',
        'resources/css/components/dropdown.css',
        'resources/css/components/persistent_header.css',
        'resources/css/components/product_card.css',
        'resources/css/components/carousel_scrollable.css',
    ])
@endsection

@section('tail')
    @vite([
        'resources/js/components/dropdown.js',
        'resources/js/components/persistent_header.js',
        'resources/js/components/carousel_scrollable.js',
        'resources/js/components/two_arrow_scroller.js',
        'resources/js/components/side_panel.js',
        'resources/js/components/product_quantity_picker.js',
    ])
    {{-- @vite('resources/js/components/product_card.js') --}}
@endsection

@section('content')
    <section>
        <div class="top-banner">
            <svg class="logo-larpshop-horizontal">
                <use href="/images/logo.svg#larpshop-horizontal"></use>
            </svg>
        </div>
    </section>
    <header class="container sticky-top" style="background-color: #ffffff99;">
        @vite([
            'resources/css/top_header.css',
            'resources/css/components/navigation_bar.css',
        ])
        <x-nav-bar
            product-cart-bs-target="#side-panel"
            product-cart-bs-toggle="offcanvas"
            :product-categories="$productCategories"
            :cart="$cart"
        />
        <div class="persistent-header position-relative mt-2 px-4 py-2 z-0">
            <ul class="persistent-header-content d-flex align-items-center gap-3 p-0 overflow-scroll" style="list-style: none;">
                <li>
                    <button
                        type="button"
                        class="side-panel-trigger nav-button rounded-pill border-0"
                        data-bs-target="#side-panel"
                        data-bs-toggle="offcanvas"
                        data-side-panel-target="#settings"
                    >
                        <svg class="icon-16">
                            <use href="/images/icons.svg#settings-sliders"></use>
                        </svg>
                        <span> Settings </span>
                        <template id="settings">
                            @auth
                                <span class="text-center">Sad setting form with nothing other than the logout button</span>
                                <form action="{{ route('logout') }}" method="post">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="nav-button rounded-pill border-0"
                                    >
                                        Logout
                                    </button>
                                </form>
                            @endauth
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
        <section id="main-banners" class="container px-lg-5 my-5">
            <div class="row g-4">
                <div class="col-6 col-lg-3 d-flex flex-column overflow-hidden">
                    <div class="row g-4 flex-grow-1">
                        <div class="col-12 h-25">
                            <x-category-banner
                                title="Cameras"
                                slug="cameras"
                                image="/images/17067741-camera-1x1.jpg"
                                alt="Camera collection"
                                ratio="ratio-21x9"
                            />
                        </div>
                        <div class="col-12 h-75">
                            <x-category-banner
                                title="Home & Living"
                                slug="home-living"
                                image="/images/aestheticjourney-cream-1x1.jpg"
                                alt="Home and living collection"
                                ratio="ratio-1x1"
                            />
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-5">
                    <x-category-banner
                        title="Makeup"
                        slug="makeup"
                        image="/images/8618939-makeup-150.jpg"
                        alt="Makeup collection"
                        custom-ratio="150%"
                    />
                </div>
                <div class="col-12 col-lg-4 d-lg-flex flex-lg-column overflow-hidden">
                    <div class="row g-4 h-100 flex-lg-grow-1">
                        <div class="col-5 col-lg-12">
                            <x-category-banner
                                title="Kitchenware"
                                slug="kitchenware"
                                image="/images/viarami-product-5222331_150.jpg"
                                alt="Kitchenware collection"
                                custom-ratio="67.67%"
                            />
                        </div>
                        <div class="col-7 col-lg-12 h-100">
                            <x-category-banner
                                title="Fabric"
                                slug="fabric"
                                image="/images/engin_akyurt-fabric-2405110_66.jpg"
                                alt="Fabric collection"
                                custom-ratio="67.67%"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section>
            @vite('resources/css/broad_select.css')
            <header class="container">
                <div class="text-center m-3">
                    <h1> Get it all from one place </h1>
                    Categorized down to every item
                </div>
            </header>
            <div class="broad-select">
                <div class="d-flex selector">
                    <div id="vertical" class="carousel-scrollable position-relative overflow-hidden" data-carousel-direction="down" data-carousel-autoplay-speed="0">
                        <ul class="selector-category-list carousel-scroller ps-0 p-lg-4 overflow-y-scroll">
                            <li>
                                <button type="button" class="selector-item">
                                    <div class="d-inline-flex flex-nowrap">
                                        <h3 class="fw-bold text-uppercase p-3" style="border-radius: 12px 0 12px 0; color: white; background: conic-gradient(from 45deg at 85% 96%, #f53003 0%, #49190B 6.3%, #1F4C5A 22.1%, #182E35 35%, #362710 45.6%, rgb(255,0,55) 58%, #F5024F 62.6%, #FFC9CA 70.6%, #FF6941 75.5%, #FFA3A7 87.1%, #FFA688 93.2%, #f53003 100%);">
                                            Trending sales
                                        </h3>
                                    </div>
                                </button>
                            </li>
                            <li>
                                <button type="button" class="selector-item">
                                    <div class="d-inline-flex flex-wrap">
                                        <h3 class="fw-bold text-uppercase p-3">
                                            Foo
                                        </h3>
                                    </div>
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div id="horizontal" class="carousel-scrollable w-100 overflow-hidden" data-carousel-direction="right" data-carousel-autoplay-speed="1">
                        <ul class="d-flex gap-3 w-100 h-100 ps-0 selector-product-list carousel-scroller overflow-x-scroll" style="list-style: none;">
                            @foreach ($products as $product)
                                <li>
                                    <x-product-card-a :product="$product"/>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                <div>
            </div>
        </section>
        <section>
            <x-rounded-section-radius>
                <div class="container">
                    <header class="d-inline-flex flex-wrap position-relative pt-2 mb-4">
                        <h3 class="fw-bold text-decoration-underline text-uppercase p-3" style="color: white; background: var(--color-complementary-400);">
                            Everything Larpable
                        </h3>
                        <p>
                            Stock gathered from every nooks and cranny of the Internet. Get it all from one place, everything
                            within reach. You don't need nothing else.
                        </p>
                    </header>

                    <div style="text-align: end;">
                        <a href="{{ route('products.index') }}" class="text-decoration-none fw-semibold">
                            Browse all products <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>

                    <div class="rounded-pill py-2 my-2 overflow-hidden shadow" style="background-color: white;">
                        <x-two-arrow-scroller scroll-step="0.4">
                            <ul id="two-arrow-scrollable" class="px-4 d-flex flex-nowrap gap-3 list-unstyled overflow-scroll m-0">
                                @foreach ($productCategories as $category)
                                    <li>
                                        <a href="{{ route('categories.show', ['slug' => $category->slug ?? $category['slug'] ?? '']) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 text-nowrap text-decoration-none">
                                            {{ $category->name ?? $category['name'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </x-two-arrow-scroller>
                    </div>

                    <x-grid
                        class="d-flex"
                        gap="4"
                        :columns="[
                            'default' => 2,
                            'md' => 3,
                            'xl' => 6,
                        ]"
                    >
                        @foreach ($products as $product)
                            <x-product-card-b class="col" :product="$product"/>
                        @endforeach
                    </x-grid>
                </div>
            </x-rounded-section-radius>
        </section>
    </main>
    <x-side-panel/>
    <x-modal/>
    <x-company-footer/>
@endsection
