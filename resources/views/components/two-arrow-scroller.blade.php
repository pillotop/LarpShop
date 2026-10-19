@props([
    'scrollStep' => null,
])

<div class="two-arrow-scroller position-relative" data-scroll-step="{{ $scrollStep }}">
    <button id="arrow-left" class="arrow-btn position-absolute start-0 h-100" type="button">
        <svg class="icon-24"> <use href="/images/icons.svg#arrow-left"></use> </svg>
    </button>
    <button id="arrow-right" class="arrow-btn position-absolute end-0 h-100" type="button">
        <svg class="icon-24"> <use href="/images/icons.svg#arrow-right"></use> </svg>
    </button>

    {{ $slot }}
</div>
