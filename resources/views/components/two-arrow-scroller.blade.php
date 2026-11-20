@props([
    'scrollStep' => null,
    'contentId' => null,
])

<div class="two-arrow-scroller position-relative" data-scroll-step="{{ $scrollStep }}">
    <button
        @if ($contentId)
            aria-controls="{{ $contentId }}"
        @endif
        data-scroll-previous
        aria-label="{{ __('products.scroll_previous') }}"
        class="arrow-btn position-absolute start-0 h-100"
        type="button"
    >
        <svg class="icon-24" aria-hidden="true" focusable="false"> <use href="/images/icons.svg#arrow-left"></use> </svg>
    </button>
    <button
        @if ($contentId)
            aria-controls="{{ $contentId }}"
        @endif
        data-scroll-next
        aria-label="{{ __('products.scroll_next') }}"
        class="arrow-btn position-absolute end-0 h-100"
        type="button"
    >
        <svg class="icon-24" aria-hidden="true" focusable="false"> <use href="/images/icons.svg#arrow-right"></use> </svg>
    </button>

    {{ $slot }}
</div>
