@props(['product', 'reviews', 'canReview'])

<section
    id="product-reviews"
    class="container my-5"
    aria-labelledby="product-reviews-heading"
>
    <div class="row g-4">
        <aside class="col-12 lg:col-4">
            <div class="bg-1 rounded-9 p-4 h-100">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <span class="rounded-circle bg-subtle-primary p-4 fs-xl fw-bold" aria-hidden="true">
                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($product->vendor?->store_name ?? 'L', 0, 1)) }}
                    </span>
                    <h3 class="h5 mb-0">{{ $product->vendor?->store_name ?? __('products.detail.partner') }}</h3>
                </div>

                @if ($product->vendor)
                    @if ($product->vendor->created_at)
                        <p class="fg-3 small">
                            <i class="bi bi-calendar3 me-2" aria-hidden="true"></i>
                            {{ __('reviews.joined', ['date' => $product->vendor->created_at->locale(app()->getLocale())->translatedFormat('M Y')]) }}
                        </p>
                    @endif
                    <a
                        href="{{ route('vendors.show', $product->vendor) }}"
                        class="btn btn-solid theme-primary rounded-pill w-100"
                    >
                        {{ __('reviews.visit_store') }}
                    </a>
                @endif
            </div>
        </aside>

        <div class="col-12 lg:col-8">
            <h2 id="product-reviews-heading" class="h4">{{ __('reviews.title') }}</h2>
            <p class="fg-3">
                {{ __('reviews.rating_summary', ['rating' => number_format($product->average_rating ?? 0, 1)]) }}
                · {{ trans_choice('reviews.count', $product->total_reviews, ['count' => $product->total_reviews]) }}
            </p>

            <x-review-feedback />

            <div data-product-reviews aria-live="polite">
                @include('products.partials.review-list', ['product' => $product, 'reviews' => $reviews])
            </div>

            @if ($canReview)
                <x-review-form
                    :product="$product"
                    :id="'review-form-product-' . $product->getKey()"
                />
            @else
                <p class="small fg-3 mt-4">
                    {{ __('reviews.eligibility') }}
                </p>
            @endif
        </div>
    </div>
</section>
