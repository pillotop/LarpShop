@props([
    'scrollStep' => null,
    'contentId' => null,
    'plainArrows' => false,
])

<div
    {{ $attributes->class(['two-arrow-scroller position-relative']) }}
    data-scroll-step="{{ $scrollStep }}"
>
    <button
        @if ($contentId)
            aria-controls="{{ $contentId }}"
        @endif
        data-scroll-previous
        aria-label="{{ __('products.scroll_previous') }}"
        class="arrow-btn position-absolute start-0 h-100"
        type="button"
    >
        <svg
            class="icon-24"
            @if ($plainArrows)
                viewBox="0 0 24 24"
            @endif
            aria-hidden="true"
            focusable="false"
        >
            @if ($plainArrows)
                <path
                    d="m15 6-6 6 6 6"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                ></path>
            @else
                <use href="/images/icons.svg#arrow-left"></use>
            @endif
        </svg>
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
        <svg
            class="icon-24"
            @if ($plainArrows)
                viewBox="0 0 24 24"
            @endif
            aria-hidden="true"
            focusable="false"
        >
            @if ($plainArrows)
                <path
                    d="m9 6 6 6-6 6"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                ></path>
            @else
                <use href="/images/icons.svg#arrow-right"></use>
            @endif
        </svg>
    </button>

    {{ $slot }}
</div>
