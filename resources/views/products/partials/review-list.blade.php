@forelse ($reviews as $review)
    <x-products.review :review="$review" />
@empty
    <p class="fg-3">{{ __('reviews.empty') }}</p>
@endforelse

@if ($reviews->hasMorePages())
    <div
        data-review-pagination
        data-loading-message="{{ __('reviews.loading') }}"
        data-error-message="{{ __('reviews.load_error') }}"
    >
        <a
            href="{{ route('products.show', [$product, $product->slug, 'reviews_page' => $reviews->currentPage() + 1]) }}#product-reviews"
            data-more-reviews="{{ route('reviews.index', [$product, 'reviews_page' => $reviews->currentPage() + 1]) }}"
            class="btn btn-outline theme-primary rounded-pill px-4"
        >
            {{ __('reviews.more') }}
        </a>
        <p class="small fg-danger mt-2 mb-0" data-review-error role="status"></p>
    </div>
@endif
