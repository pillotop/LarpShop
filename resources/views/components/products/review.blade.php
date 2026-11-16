@props([
    'review',
    'canReply' => false,
])

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
        <span class="fw-semibold fg-body">{{ $review->user?->username ?? __('reviews.customer') }}</span>
        <time datetime="{{ $review->created_at->toIso8601String() }}">
            {{ $review->created_at->locale(app()->getLocale())->diffForHumans() }}
        </time>
        @if ($review->verified_purchase)
            <span class="badge bg-subtle-success fg-success border border-success rounded-pill">
                <i class="bi bi-patch-check-fill me-1" aria-hidden="true"></i>{{ __('reviews.verified') }}
            </span>
        @endif
    </div>

    @if ($review->comment)
        <p class="mb-0 text-break" style="white-space: pre-line;">{{ $review->comment }}</p>
    @else
        <p class="mb-0 fg-3">{{ __('reviews.no_comment') }}</p>
    @endif

    @if ($review->vendor_reply)
        <div class="mt-3 ms-3 p-3 rounded-4 bg-1 border-start border-3 border-primary">
            <div class="d-flex flex-wrap align-items-center gap-2 small fw-semibold mb-1">
                <i class="bi bi-shop fg-primary" aria-hidden="true"></i>
                <span>{{ __('reviews.seller_reply') }}</span>
                @if ($review->product?->vendor?->store_name)
                    <span class="fg-3">· {{ $review->product->vendor->store_name }}</span>
                @endif
                @if ($review->vendor_replied_at)
                    <span class="fg-3 fw-normal ms-auto">{{ $review->vendor_replied_at->locale(app()->getLocale())->diffForHumans() }}</span>
                @endif
            </div>
            <p class="small mb-0 text-break" style="white-space: pre-line;">{{ $review->vendor_reply }}</p>
        </div>
    @endif

    @if ($canReply)
        <details class="mt-2">
            <summary class="small fg-primary fw-semibold" style="cursor: pointer;">
                {{ $review->vendor_reply ? __('reviews.reply_update') : __('reviews.reply_submit') }}
            </summary>
            <form action="{{ route('vendor.reviews.reply', $review) }}" method="POST" class="mt-2 d-flex flex-column gap-2">
                @csrf
                <textarea
                    name="vendor_reply"
                    rows="2"
                    maxlength="1000"
                    class="form-control form-control-sm"
                    placeholder="{{ __('reviews.reply_placeholder') }}"
                    required
                >{{ old('vendor_reply', $review->vendor_reply) }}</textarea>
                <div>
                    <button type="submit" class="btn btn-sm btn-solid theme-primary rounded-pill px-3">
                        {{ $review->vendor_reply ? __('reviews.reply_update') : __('reviews.reply_submit') }}
                    </button>
                </div>
            </form>
        </details>
    @endif
</article>
