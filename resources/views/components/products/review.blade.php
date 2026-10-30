@props(['review'])

<article class="mb-4 pb-3 border-bottom">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-2 small text-muted">
        <span class="text-warning" aria-label="{{ $review->rating }} out of 5 stars">
            @for ($star = 1; $star <= 5; $star++)
                <i
                    class="bi {{ $star <= $review->rating ? 'bi-star-fill' : 'bi-star' }}"
                    aria-hidden="true"
                ></i>
            @endfor
        </span>
        <span>{{ $review->user?->username ?? 'Customer' }}</span>
        <time datetime="{{ $review->created_at->toIso8601String() }}">
            {{ $review->created_at->diffForHumans() }}
        </time>
        @if ($review->verified_purchase)
            <span class="text-success">Verified purchase</span>
        @endif
    </div>

    @if ($review->comment)
        <p class="mb-0 text-break" style="white-space: pre-line;">{{ $review->comment }}</p>
    @else
        <p class="mb-0 text-muted">This customer left a rating without a comment.</p>
    @endif
</article>
