@extends('layouts.app')

@section('title', __('products.home.title'))

@section('head')

    @vite([
        'resources/css/icons.css',
        'resources/css/components/category_discovery.css',
        'resources/css/broad_select.css',
        'resources/css/components/promo_sections.css',
        'resources/css/components/banner_groups.css',
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
        <div data-homepage-layout>
            @foreach ($homepageBlocks as $block)
                @if ($block['type'] === 'banner')
                    <div class="container lg:px-5 my-5" data-homepage-block="banner:{{ $block['id'] }}">
                        <x-banner-group :group="$block['group']" :priority="$loop->first" :load-assets="false" />
                    </div>
                @elseif ($block['type'] === 'promo')
                    <x-promo-sections-deferred :section-id="$block['id']" />
                @elseif ($block['system_kind'] === 'broad_select')
                    <div data-homepage-block="system:broad_select">
                        <x-broad-select.deferred />
                    </div>
                @elseif ($block['system_kind'] === 'category_discovery')
                    <div data-homepage-block="system:category_discovery">
                        <x-category-discovery :categories="$productCategories" />
                    </div>
                @endif
            @endforeach
        </div>
    </main>
    <x-side-panel/>
    <x-modal/>
    <x-company-footer/>
@endsection
