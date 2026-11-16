@forelse ($reviews as $review)
    <x-products.review :review="$review" :can-reply="$canReplyToReviews ?? false" />
@empty
    <p class="fg-3">{{ __('reviews.empty') }}</p>
@endforelse

@if ($reviews->hasMorePages())
    @php
        $nextPageParams = array_filter([
            'reviews_page' => $reviews->currentPage() + 1,
            'reviews_rating' => $reviewFilters['rating'] ?? null,
            'reviews_commented' => !empty($reviewFilters['commented']) ? '1' : null,
        ], fn ($v) => $v !== null);
    @endphp
    <div
        data-review-pagination
        data-loading-message="{{ __('reviews.loading') }}"
        data-error-message="{{ __('reviews.load_error') }}"
    >
        <a
            href="{{ route('products.show', [$product, $product->slug, ...$nextPageParams]) }}#product-reviews"
            data-more-reviews="{{ route('reviews.index', [$product, ...$nextPageParams]) }}"
            class="btn btn-outline theme-primary rounded-pill px-4"
        >
            {{ __('reviews.more') }}
        </a>
        <p class="small fg-danger mt-2 mb-0" data-review-error role="status"></p>
    </div>
@endif
