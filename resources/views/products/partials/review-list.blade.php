@forelse ($reviews as $review)
    <x-products.review :review="$review" />
@empty
    <p class="text-muted">No reviews yet.</p>
@endforelse

@if ($reviews->hasMorePages())
    <div data-review-pagination>
        <a
            href="{{ route('products.show', [$product, $product->slug, 'reviews_page' => $reviews->currentPage() + 1]) }}#product-reviews"
            data-more-reviews="{{ route('reviews.index', [$product, 'reviews_page' => $reviews->currentPage() + 1]) }}"
            class="btn btn-outline-primary rounded-pill px-4"
        >
            Show more reviews
        </a>
        <p class="small text-danger mt-2 mb-0" data-review-error role="status"></p>
    </div>
@endif
