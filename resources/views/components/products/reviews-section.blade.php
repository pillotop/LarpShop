@props(['product', 'reviews', 'canReview'])

<section
    id="product-reviews"
    class="container my-5"
    aria-labelledby="product-reviews-heading"
>
    <div class="row g-4">
        <aside class="col-12 col-lg-4">
            <div class="bg-light rounded-4 p-4 h-100">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <span class="rounded-circle bg-primary-subtle p-4 fs-3 fw-bold" aria-hidden="true">
                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($product->vendor?->store_name ?? 'L', 0, 1)) }}
                    </span>
                    <h3 class="h5 mb-0">{{ $product->vendor?->store_name ?? 'LarpShop Partner' }}</h3>
                </div>

                @if ($product->vendor)
                    @if ($product->vendor->created_at)
                        <p class="text-muted small">
                            <i class="bi bi-calendar3 me-2" aria-hidden="true"></i>
                            Joined {{ $product->vendor->created_at->format('M Y') }}
                        </p>
                    @endif
                    <a
                        href="{{ route('vendors.show', $product->vendor) }}"
                        class="btn btn-primary rounded-pill w-100"
                    >
                        Visit store
                    </a>
                @endif
            </div>
        </aside>

        <div class="col-12 col-lg-8">
            <h2 id="product-reviews-heading" class="h4">Product reviews</h2>
            <p class="text-muted">
                {{ number_format($product->average_rating ?? 0, 1) }} out of 5
                · {{ $product->total_reviews }} reviews
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
                <p class="small text-muted mt-4">
                    Customers can review each product once after a paid purchase.
                </p>
            @endif
        </div>
    </div>
</section>
