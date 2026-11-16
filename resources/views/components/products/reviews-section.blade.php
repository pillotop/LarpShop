@props([
    'product',
    'reviews',
    'canReview',
    'ratingBreakdown' => [],
    'reviewFilters' => [],
    'canReplyToReviews' => false,
])

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
            <p class="fg-3 mb-3">
                {{ __('reviews.rating_summary', ['rating' => number_format($product->average_rating ?? 0, 1)]) }}
                · {{ trans_choice('reviews.count', $product->total_reviews, ['count' => $product->total_reviews]) }}
            </p>

            @if (! empty($ratingBreakdown))
                <div class="card border-0 bg-1 rounded-4 p-3 mb-4" data-review-histogram>
                    <div class="d-flex flex-column gap-2">
                        @foreach ($ratingBreakdown as $star => $row)
                            <div class="d-flex align-items-center gap-2 small">
                                <span class="text-nowrap fw-semibold" style="width: 3.5rem;">{{ $star }} ★</span>
                                <div class="progress flex-grow-1" style="height: 8px;" role="progressbar" aria-valuenow="{{ $row['percent'] }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar bg-warning" style="width: {{ $row['percent'] }}%;"></div>
                                </div>
                                <span class="fg-3 text-end" style="width: 2.5rem;">{{ $row['count'] }}</span>
                            </div>
                        @endforeach
                    </div>

                    @php
                        $activeRating = $reviewFilters['rating'] ?? null;
                        $commentedOnly = ! empty($reviewFilters['commented']);
                    @endphp
                    <div class="d-flex flex-wrap gap-2 mt-3 pt-3 border-top" data-review-filters>
                        <a
                            href="{{ route('products.show', [$product, $product->slug]) }}#product-reviews"
                            data-review-filter="{{ route('reviews.index', $product) }}"
                            @class([
                                'btn btn-sm rounded-pill px-3',
                                'btn-solid theme-primary' => $activeRating === null && ! $commentedOnly,
                                'btn-outline theme-secondary bg-white' => $activeRating !== null || $commentedOnly,
                            ])
                        >{{ __('reviews.filter_all') }}</a>

                        @for ($star = 5; $star >= 1; $star--)
                            <a
                                href="{{ route('products.show', [$product, $product->slug, 'reviews_rating' => $star]) }}#product-reviews"
                                data-review-filter="{{ route('reviews.index', [$product, 'reviews_rating' => $star]) }}"
                                @class([
                                    'btn btn-sm rounded-pill px-3',
                                    'btn-solid theme-primary' => $activeRating === $star,
                                    'btn-outline theme-secondary bg-white' => $activeRating !== $star,
                                ])
                            >{{ $star }} ★ ({{ $ratingBreakdown[$star]['count'] ?? 0 }})</a>
                        @endfor

                        <a
                            href="{{ route('products.show', [$product, $product->slug, 'reviews_commented' => 1]) }}#product-reviews"
                            data-review-filter="{{ route('reviews.index', [$product, 'reviews_commented' => 1]) }}"
                            @class([
                                'btn btn-sm rounded-pill px-3',
                                'btn-solid theme-primary' => $commentedOnly && $activeRating === null,
                                'btn-outline theme-secondary bg-white' => ! $commentedOnly || $activeRating !== null,
                            ])
                        >{{ __('reviews.filter_commented') }}</a>
                    </div>
                </div>
            @endif

            <x-review-feedback />

            <div data-product-reviews aria-live="polite">
                @include('products.partials.review-list', [
                    'product' => $product,
                    'reviews' => $reviews,
                    'reviewFilters' => $reviewFilters,
                    'canReplyToReviews' => $canReplyToReviews,
                ])
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
