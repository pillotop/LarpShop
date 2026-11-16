{{-- resources/views/products/pages/show.blade.php --}}
{{-- Product detail page (Bootstrap 5) --}}

@extends('layouts.app')

@section('title', __('products.detail.title', ['name' => $product->name]))

@section('head')
    @vite('resources/css/icons.css')

    @vite([
        'resources/css/company_footer.css',
        'resources/css/components/product_card.css',
        'resources/css/components/carousel_scrollable.css',
        'resources/css/components/dropdown.css',
        'resources/css/components/persistent_header.css',
        'resources/css/components/rating_container.css',
        'resources/css/components/two_arrow_scroller.css',
    ])
@endsection

@section('tail')
    @vite([
        'resources/js/components/product_reviews.js',
        'resources/js/components/product_quantity_picker.js',
        'resources/js/components/dropdown.js',
        'resources/js/components/persistent_header.js',
        'resources/js/components/carousel_scrollable.js',
        'resources/js/components/two_arrow_scroller.js',
        'resources/js/components/side_panel.js',
    ])
@endsection

@section('content')
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
        <section class="container">
            {{ Breadcrumbs::render('product', $product) }}
            <div class="row g-4 my-4">
                <div class="col-12 lg:col-10 mt-4">
                    <div class="row">
                        <div class="d-block col-12 lg:col-6 h-100">
                            <div class="carousel slide position-relative" id="previewCarousel">
                                <div class="carousel-inner rounded overflow-hidden">
                                    @forelse ($product->images as $image)
                                        @php
                                            $imgSrc = \Illuminate\Support\Str::startsWith($image->path, ['http://', 'https://'])
                                                ? $image->path
                                                : asset('storage/' . ltrim($image->path, '/'));
                                        @endphp
                                        <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                                            <img src="{{ $imgSrc }}" alt="{{ $image->alt_text ?? $product->name }}" class="d-block w-100 object-fit-cover" style="max-height: 480px;">
                                        </div>
                                    @empty
                                        <div class="carousel-item active">
                                            <img src="{{ asset('images/product-placeholder.jpg') }}" alt="{{ $product->name }}" class="d-block w-100 object-fit-cover" style="max-height: 480px;">
                                        </div>
                                    @endforelse
                                </div>
                                @if ($product->images->count() > 1)
                                    <button class="carousel-control-prev" type="button" data-bs-target="#previewCarousel" data-bs-slide="prev">
                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">{{ __('products.scroll_previous') }}</span>
                                    </button>
                                    <button class="carousel-control-next" type="button" data-bs-target="#previewCarousel" data-bs-slide="next">
                                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">{{ __('products.scroll_next') }}</span>
                                    </button>
                                @endif
                            </div>
                            @if ($product->images->count() > 1)
                                <div class="carousel-scrollable mt-3">
                                    <ul class="d-flex flex-nowrap gap-3 overflow-x-auto list-unstyled carousel-scroller p-0 mb-0">
                                        @foreach ($product->images as $image)
                                            @php
                                                $imgSrc = \Illuminate\Support\Str::startsWith($image->path, ['http://', 'https://'])
                                                    ? $image->path
                                                    : asset('storage/' . ltrim($image->path, '/'));
                                            @endphp
                                            <li>
                                                <button type="button"
                                                        class="btn p-0 rounded border {{ $loop->first ? 'border-primary border-2' : '' }} overflow-hidden"
                                                        data-bs-target="#previewCarousel"
                                                        data-bs-slide-to="{{ $loop->index }}"
                                                        data-carousel-thumb-index="{{ $loop->index }}"
                                                        aria-label="{{ $image->alt_text ?? $product->name }}">
                                                    <img src="{{ $imgSrc }}" alt="{{ $image->alt_text ?? $product->name }}" class="d-block" style="height: 80px; width: 80px; object-fit: cover;">
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <div class="my-5">
                                <article class="prose prose-invert max-w-2xl mx-auto">
                                    <h2 class="h3 mb-4">{{ $product->name }}</h2>
                                    <p>{{ $product->description }}</p>

                                    @if ($product->stock > 0)
                                        <div class="quantity-picker d-flex flex-column gap-3"
                                             data-quantity-mode="add"
                                             data-add-to-cart-url="{{ route('cart.add') }}"
                                             data-product-id="{{ $product->product_id }}">
                                            @csrf
                                            <label for="product-purchase-quantity" class="form-label mb-0">{{ __('products.detail.quantity') }}</label>
                                            <div class="input-group" style="max-width: 10rem;">
                                                <button type="button" data-quantity-decrement class="btn btn-solid theme-secondary fw-semibold" aria-label="{{ __('products.detail.decrease') }}">−</button>
                                                <input id="product-purchase-quantity" data-quantity-counter type="number" min="1" max="{{ $product->stock }}" step="1" value="1" class="form-control text-center">
                                                <button type="button" data-quantity-increment class="btn btn-solid theme-secondary fw-semibold" aria-label="{{ __('products.detail.increase') }}">+</button>
                                            </div>
                                            <button type="button" data-buy-now="{{ route('cart.index') }}" class="w-100 button-primary">{{ __('products.detail.buy_now') }}</button>
                                            <button type="button" data-add-to-cart class="w-100 button-primary">{{ __('products.detail.add_to_cart') }}</button>
                                            <span class="cart-feedback visually-hidden" role="status" aria-live="polite"></span>
                                        </div>
                                    @endif
                                </article>
                            </div>
                        </div>
                        <div class="col-12 lg:col-6" data-bs-spy="scroll" data-bs-target="#product" data-bs-smooth-scroll="true">
                            <div id="product-header" class="mb-4 border-bottom pb-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-shop me-1"></i>{{ $product->vendor?->store_name ?? __('products.detail.partner') }}
                                    </span>
                                    @if ($product->category)
                                        <a href="{{ route('categories.show', ['slug' => $product->category->slug]) }}" class="badge bg-1 fg-body text-decoration-none border">
                                            {{ $product->category->name }}
                                        </a>
                                    @endif
                                </div>
                                <h1 class="h3 mb-3">{{ $product->name }}</h1>
                                <x-wishlist.button :product="$product" />
                                <p class="fg-secondary mb-4">{{ $product->description }}</p>

                                <div class="d-flex align-items-baseline gap-2">
                                    <span id="product-detail-price" class="fs-3xl fw-bold fg-primary">${{ number_format($product->price, 2) }}</span>
                                    @if ($product->stock > 0)
                                        <span id="product-detail-stock" class="fg-success small">{{ __('products.detail.in_stock', ['count' => $product->stock]) }}</span>
                                    @else
                                        <span id="product-detail-stock" class="fg-danger small">{{ __('products.detail.out_of_stock') }}</span>
                                    @endif
                                </div>
                                <x-rating-container average-rating="{{ $product->average_rating }}" total-reviews="{{ $product->total_reviews }}" />
                                @if ($product->vendor)
                                    <div class="mt-2 mb-3">
                                        <span class="fg-3 small">{{ __('products.detail.sold_by') }}</span>
                                        <a href="{{ route('vendors.show', ['vendor' => $product->vendor->vendor_id]) }}" class="fg-primary text-decoration-none fw-semibold">
                                            <i class="bi bi-shop me-1"></i>{{ $product->vendor->store_name }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                            <div id="product-variants" class="mb-4 border-bottom pb-3">
                                @php
                                    $activeVariants = $product->relationLoaded('variants')
                                        ? $product->variants->where('is_active', true)->values()
                                        : $product->variants()->active()->get();
                                @endphp
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h2 class="h6 fw-bold mb-0">{{ __('products.detail.variants') }}</h2>
                                    @if ($activeVariants->isNotEmpty())
                                        <span class="small fg-3">
                                            {{ __('products.detail.selected_option') }}
                                            <strong id="product-selected-variant-name" class="fg-body">{{ __('products.detail.base_edition') }}</strong>
                                        </span>
                                    @endif
                                </div>
                                @if ($activeVariants->isNotEmpty())
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button"
                                                class="btn text-start border border-primary bg-white shadow-xs rounded-5 p-2 px-3 d-flex align-items-center gap-2"
                                                data-variant-option
                                                data-variant-name="{{ __('products.detail.base_edition') }}"
                                                data-variant-price="${{ number_format($product->price, 2) }}"
                                                data-variant-stock="{{ $product->stock }}"
                                                data-variant-stock-label="{{ $product->stock > 0 ? __('products.detail.in_stock', ['count' => $product->stock]) : __('products.detail.out_of_stock') }}"
                                                aria-pressed="true">
                                            <div>
                                                <div class="fw-semibold small">{{ __('products.detail.base_edition') }}</div>
                                                <div class="fg-success small fw-bold">${{ number_format($product->price, 2) }}</div>
                                            </div>
                                            @if ($product->stock <= 0)
                                                <span class="badge bg-danger small ms-1">{{ __('products.out_of_stock') }}</span>
                                            @else
                                                <span class="badge bg-subtle-secondary fg-body small ms-1">{{ __('products.detail.remaining', ['count' => $product->stock]) }}</span>
                                            @endif
                                        </button>
                                        @foreach ($activeVariants as $variant)
                                            <button type="button"
                                                    class="btn text-start border rounded-5 p-2 px-3 bg-1 d-flex align-items-center gap-2"
                                                    data-variant-option
                                                    data-variant-name="{{ $variant->name }}"
                                                    data-variant-price="${{ number_format($variant->price, 2) }}"
                                                    data-variant-stock="{{ $variant->stock }}"
                                                    data-variant-stock-label="{{ $variant->stock > 0 ? __('products.detail.in_stock', ['count' => $variant->stock]) : __('products.detail.out_of_stock') }}"
                                                    aria-pressed="false">
                                                <div>
                                                    <div class="fw-semibold small">{{ $variant->name }}</div>
                                                    <div class="fg-success small fw-bold">${{ number_format($variant->price, 2) }}</div>
                                                </div>
                                                @if ($variant->stock <= 0)
                                                    <span class="badge bg-danger small ms-1">{{ __('products.out_of_stock') }}</span>
                                                @else
                                                    <span class="badge bg-subtle-secondary fg-body small ms-1">{{ __('products.detail.remaining', ['count' => $variant->stock]) }}</span>
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="fg-3 small mb-0">{{ __('products.detail.standard') }}</p>
                                @endif
                            </div>
                            <div id="shipping-options" class="mb-4 border-bottom pb-3">
                                <form>
                                    <h2 class="h5 mb-3">{{ __('products.detail.delivery') }}</h2>

                                    <label class="d-flex align-items-start gap-2 p-2 rounded-3 border bg-1 mb-2" style="cursor: pointer;">
                                        <input name="means" value="pickup" type="radio" class="radio mt-1">
                                        <i class="bi bi-shop fs-lg mt-1"></i>
                                        <div>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="fw-bold small">{{ __('products.detail.pickup') }}</span>
                                                <span class="fg-3 small">{{ __('products.detail.or') }}</span>
                                            </div>
                                            <div class="small fg-3">{{ __('products.detail.pickup_desc') }}</div>
                                        </div>
                                    </label>

                                    <label class="d-flex align-items-start gap-2 p-2 rounded-3 border bg-1" style="cursor: pointer;">
                                        <input name="means" value="home_delivery" type="radio" class="radio mt-1" checked>
                                        <i class="bi bi-truck fs-lg mt-1"></i>
                                        <div>
                                            <div class="fw-bold small">{{ __('products.detail.home_delivery') }}</div>
                                            <div class="small fg-3">{{ __('products.detail.home_delivery_desc') }}</div>
                                        </div>
                                    </label>
                                </form>
                            </div>
                            <div id="warranty" class="mb-4 border-bottom pb-3">
                                <div class="d-flex align-items-start gap-3 p-3 rounded-4 bg-1 border">
                                    <i class="bi bi-shield-check fs-2xl fg-success mt-1" aria-hidden="true"></i>
                                    <div>
                                        <h2 class="h6 fw-bold mb-1">{{ __('products.detail.warranty') }}</h2>
                                        <p class="small fw-semibold mb-1">{{ __('products.detail.warranty_summary') }}</p>
                                        <p class="small fg-3 mb-0">{{ __('products.detail.warranty_details') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 lg:col-2 lg:sticky-top">
                    <nav id="product" class="d-none lg:d-flex flex-column align-items-stretch">
                        <nav class="nav nav flex-column">
                            <a class="nav-link text-truncate" href="#product-header">{{ $product->name }}</a>
                            <nav class="nav nav flex-column">
                                <a class="nav-link ms-3 my-1" href="#product-variants">{{ __('products.detail.product_variants') }}</a>
                                <a class="nav-link ms-3 my-1" href="#shipping-options">{{ __('products.detail.shipping') }}</a>
                                <a class="nav-link ms-3 my-1" href="#warranty">{{ __('products.detail.warranty') }}</a>
                            </nav>
                        </nav>
                    </nav>
                    <div class="accordion accordion-flush" id="aside">
                        <details id="faq" class="accordion-item" name="aside">
                            <summary class="accordion-header">
                                {{ __('products.detail.faq') }}
                                <i class="bi bi-chevron-down accordion-icon" aria-hidden="true"></i>
                            </summary>
                            <div class="accordion-panel">
                                <div class="accordion-body small">
                                    <p class="fw-semibold mb-1">{{ __('products.detail.faq_ship_q') }}</p>
                                    <p class="fg-3 mb-2">{{ __('products.detail.faq_ship_a') }}</p>
                                    <p class="fw-semibold mb-1">{{ __('products.detail.faq_return_q') }}</p>
                                    <p class="fg-3 mb-2">{{ __('products.detail.faq_return_a') }}</p>
                                    <a href="{{ route('help') }}" class="fg-primary text-decoration-none fw-semibold">
                                        {{ __('products.detail.help_link') }} <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </div>
                        </details>
                        <details id="seller-options" class="accordion-item" name="aside">
                            <summary class="accordion-header">
                                {{ __('products.detail.seller') }}
                                <i class="bi bi-chevron-down accordion-icon" aria-hidden="true"></i>
                            </summary>
                            <div class="accordion-panel">
                                <div class="accordion-body">
                                    <p class="mb-1 fw-bold">{{ $product->vendor?->store_name ?? __('products.detail.partner') }}</p>
                                    <p class="small fg-3 mb-2">{{ __('products.detail.status', ['status' => __('products.vendor_status.' . ($product->vendor?->status ?? 'active'))]) }}</p>
                                    @if ($product->vendor)
                                        <a href="{{ route('vendors.show', ['vendor' => $product->vendor->vendor_id]) }}" class="small fg-primary text-decoration-none fw-semibold">
                                            {{ __('products.detail.visit_store') }} <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </details>
                    </div>
                </div>
            </div>
        </section>
        <x-products.reviews-section
            :product="$product"
            :reviews="$reviews"
            :can-review="$canReview"
        />

        <section class="container my-5">
            <h2 class="h4 fw-bold mb-3">{{ __('products.detail.similar') }}</h2>
            @if ($similarProducts->isNotEmpty())
                <x-two-arrow-scroller>
                    <ul id="two-arrow-scrollable" class="d-flex flex-nowrap gap-3 list-unstyled overflow-x-auto m-0 py-2">
                        @foreach ($similarProducts as $similarProduct)
                            <li>
                                <x-product-card-b :product="$similarProduct"/>
                            </li>
                        @endforeach
                    </ul>
                </x-two-arrow-scroller>
            @else
                <div class="p-4 rounded bg-1 text-center fg-3">
                    <p class="mb-0">{{ __('products.detail.no_similar') }}</p>
                </div>
            @endif
        </section>
    </main>
    <x-side-panel/>
    <x-company-footer />
@endsection
