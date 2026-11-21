@extends('layouts.app')

@section('title', __('products.home.title'))

@section('head')

    @vite([
        'resources/css/icons.css',
        'resources/css/components/category_discovery.css',
        'resources/css/broad_select.css',
        'resources/css/components/promo_sections.css',
        'resources/css/category_banner.css',
        'resources/css/company_footer.css',
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
        'resources/js/components/homepage_promos.js',
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
        <section id="main-banners" class="container lg:px-5 my-5">
            <div class="row g-4">
                <div class="col-6 lg:col-3 d-flex flex-column overflow-hidden">
                    <div class="row g-4 flex-grow-1">
                        <div class="col-12 h-25">
                            <x-category-banner
                                :title="__('products.banners.cameras.title')"
                                slug="cameras"
                                image="/images/17067741-camera-1x1.jpg"
                                :alt="__('products.banners.cameras.alt')"
                                ratio="ratio-21x9"
                            />
                        </div>
                        <div class="col-12 h-75">
                            <x-category-banner
                                :title="__('products.banners.home_living.title')"
                                slug="home-and-living"
                                image="/images/aestheticjourney-cream-1x1.jpg"
                                :alt="__('products.banners.home_living.alt')"
                                ratio="ratio-1x1"
                            />
                        </div>
                    </div>
                </div>
                <div class="col-6 lg:col-5">
                    <x-category-banner
                        :title="__('products.banners.makeup.title')"
                        slug="makeup"
                        image="/images/8618939-makeup-150.jpg"
                        :alt="__('products.banners.makeup.alt')"
                        custom-ratio="150%"
                    />
                </div>
                <div class="col-12 lg:col-4 lg:d-flex lg:flex-column overflow-hidden">
                    <div class="row g-4 h-100 lg:flex-grow-1">
                        <div class="col-5 lg:col-12">
                            <x-category-banner
                                :title="__('products.banners.kitchenware.title')"
                                slug="kitchenware"
                                image="/images/viarami-product-5222331_150.jpg"
                                :alt="__('products.banners.kitchenware.alt')"
                                custom-ratio="67.67%"
                            />
                        </div>
                        <div class="col-7 lg:col-12 h-100">
                            <x-category-banner
                                :title="__('products.banners.fabric.title')"
                                slug="fabric"
                                image="/images/engin_akyurt-fabric-2405110_66.jpg"
                                :alt="__('products.banners.fabric.alt')"
                                custom-ratio="67.67%"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <x-promo-sections-deferred />
        <x-broad-select.deferred />
        <x-category-discovery :categories="$productCategories" />
    </main>
    <x-side-panel/>
    <x-modal/>
    <x-company-footer/>
@endsection
