@props(['review'])

<article class="mb-4 pb-3 border-bottom">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-2 small fg-3">
        <span class="fg-warning" aria-label="{{ __('products.rating', ['rating' => $review->rating]) }}">
            @for ($star = 1; $star <= 5; $star++)
                <i
                    class="bi {{ $star <= $review->rating ? 'bi-star-fill' : 'bi-star' }}"
                    aria-hidden="true"
                ></i>
            @endfor
        </span>
        <span>{{ $review->user?->username ?? __('reviews.customer') }}</span>
        <time datetime="{{ $review->created_at->toIso8601String() }}">
            {{ $review->created_at->locale(app()->getLocale())->diffForHumans() }}
        </time>
        @if ($review->verified_purchase)
            <span class="fg-success">{{ __('reviews.verified') }}</span>
        @endif
    </div>

    @if ($review->comment)
        <p class="mb-0 text-break" style="white-space: pre-line;">{{ $review->comment }}</p>
    @else
        <p class="mb-0 fg-3">{{ __('reviews.no_comment') }}</p>
    @endif
</article>
