{{-- resources/views/products/pages/show.blade.php --}}
{{-- Product detail page (Bootstrap 5) --}}

@extends('layouts.app')

@section('title', 'Product | ' . $product->name )

@section('head')
    <link rel="stylesheet" href="/css/icons.css">
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/company_footer.css">
    <link rel="stylesheet" href="/css/components/product_card.css">
    <link rel="stylesheet" href="/css/components/carousel_scrollable.css">
    <link rel="stylesheet" href="/css/components/dropdown.css">
    <link rel="stylesheet" href="/css/components/persistent_header.css">
    <link rel="stylesheet" href="/css/components/rating_container.css">
    <link rel="stylesheet" href="/css/components/two_arrow_scroller.css">
@endsection

@section('tail')
    <script src="/js/components/dropdown.js"></script>
    <script src="/js/components/persistent_header.js"></script>
    <script src="/js/components/carousel_scrollable.js"></script>
    <script src="/js/components/two_arrow_scroller.js"></script>
    <script src="/js/components/side_panel.js"></script>
@endsection

@section('content')
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
        <section class="container">
            {{ Breadcrumbs::render('product', $product) }}
            <div class="row g-4 my-4">
                <div class="col-12 col-lg-10 mt-4">
                    <div class="row">
                        <div class="d-block col-12 col-lg-6 h-100">
                            <div class="carousel slide position-relative">
                                <div class="carousel-inner">
                                    @foreach ($product->images as $image)
                                        <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                                            <img src="{{ $image->path }}" alt="{{ $product->name }}" class="d-block w-100">
                                        </div>
                                    @endforeach
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#previewCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Previous</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#previewCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Next</span>
                                </button>
                            </div>
                            <div class="carousel-scrollable">
                                <ul class="d-flex flex-nowrap gap-3 overflow-scroll list-unstyled carousel-scroller">
                                    @foreach ($product->images as $image)
                                        <li>
                                            <img src="{{ $image->path }}" alt="{{ $product->name }}" class="" style="max-height: 144px">
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="my-5">
                                <article class="prose prose-invert max-w-2xl mx-auto">
                                    <h2 class="h3 mb-4">{{ $product->name }}</h2>
                                    <p>{{ $product->description }}</p>

                                    @if ($product->stock > 0)
                                        <div class="d-flex flex-column gap-3">
                                            <a href="#" class="w-100">
                                                <button type="button" class="w-100 button-primary">Larp it now</button>
                                            </a>
                                            <a href="#" class="w-100">
                                                <button type="button" class="w-100 button-primary">Add to Cart</button>
                                            </a>
                                        </div>
                                    @endif
                                </article>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6" data-bs-spy="scroll" data-bs-target="#product" daba-bs-smooth-scroll="true">
                            <div id="product-header" class="mb-4 border-bottom">
                                <h1 class="h3 mb-3">{{ $product->name }}</h1>
                                <p class="text-secondary mb-4">{{ $product->description }}</p>

                                <div class="d-flex align-items-baseline gap-2">
                                    <span class="fs-1 fw-bold text-primary">${{ number_format($product->price, 2) }}</span>
                                    @if ($product->stock > 0)
                                        <span class="text-success small">In stock ({{ $product->stock }})</span>
                                    @else
                                        <span class="text-danger small">Out of stock</span>
                                    @endif
                                </div>
                                <x-rating-container average-rating="{{ $product->average_rating }}" total-reviews="{{ $product->total_reviews }}" />
                            </div>
                            <div id="product-variants" class="mb-4 border-bottom">
                                feature not yet implemented
                            </div>
                            <div id="shipping-options" class="mb-4 border-bottom">
                                <form>
                                    <h1 class="h5 mb-3">Choose how you'll get this product</h1>

                                    <label class="d-flex align-items-center justify-content-start gap-2 mb-2" style="cursor: pointer;">
                                        <i class="bi bi-shop fs-5"></i>
                                        <input name="means" type="radio" class="form-check-input mt-0">
                                        <span class="fw-bold small">Get it at the front</span>
                                        <span class="text-muted small">or...</span>
                                    </label>

                                    <label class="d-flex align-items-center justify-content-start gap-2" style="cursor: pointer;">
                                        <i class="bi bi-truck fs-5"></i>
                                        <input name="means" type="radio" class="form-check-input mt-0">
                                        <span class="fw-bold small">We'll head straight to your house</span>
                                    </label>
                                </form>
                            </div>
                            <div id="warranty" class="mb-4 border-bottom">
                                feature not yet implemented
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-2 sticky-lg-top">
                    <nav id="product" class="d-none d-lg-flex flex-column align-items-stretch">
                        <nav class="nav nav-pills flex-column">
                            <a class="nav-link text-truncate" href="#product-header">{{ $product->name }}</a>
                            <nav class="nav nav-pills flex-column">
                                <a class="nav-link ms-3 my-1" href="#product-variants">Product variants</a>
                                <a class="nav-link ms-3 my-1" href="#shipping-options">Shipping options</a>
                                <a class="nav-link ms-3 my-1" href="#warranty">Warranty</a>
                            </nav>
                        </nav>
                    </nav>
                    <div class="accordion accordion-flush" id="aside">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq" aria-expanded="false">
                                    Frequently asked questions
                                </button>
                            </h2>
                            <div id="faq" class="accordion-collapse collapse" data-bs-parent="#aside">
                                <div class="accordion-body">...</div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#seller-options" aria-expanded="false">
                                    More seller options
                                </button>
                            </h2>
                            <div id="seller-options" class="accordion-collapse collapse" data-bs-parent="#aside">
                                <div class="accordion-body">...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section>
            <header class="container">
                <span class="h4">Similar products</span>
                <x-two-arrow-scroller>
                    <ul id="two-arrow-scrollable" class="d-flex flex-nowrap gap-3 list-unstyled overflow-scroll m-0">
                        @foreach ($similarProducts as $similarProduct)
                            <li>
                                <x-product-card-b :product="$similarProduct"/>
                            </li>
                        @endforeach
                    </ul>
                </x-two-arrow-scroller>
            </header>
        </section>
    </main>
    <x-side-panel/>
    <x-company-footer />
@endsection
