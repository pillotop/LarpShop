@props(['variant' => 'b'])

<div
    {{ $attributes->class(['product-card-skeleton', 'product-card-skeleton-a' => $variant === 'a']) }}
    aria-hidden="true"
>
    @if ($variant !== 'a')
        <span class="placeholder rounded-2 product-card-skeleton-compare"></span>
    @endif

    <span class="placeholder rounded-2 product-card-skeleton-image"></span>

    <div class="product-card-skeleton-body">
        <span class="placeholder rounded-2 col-9"></span>
        <span class="placeholder rounded-2 placeholder-sm col-12"></span>
        <span class="placeholder rounded-2 placeholder-sm col-7"></span>
        <span class="placeholder rounded-2 placeholder-lg col-5 mt-2"></span>
        <span class="placeholder rounded-2 placeholder-sm col-6"></span>
    </div>

    <div class="product-card-skeleton-actions">
        <span class="placeholder product-card-skeleton-circle"></span>
        @if ($variant === 'a')
            <span class="placeholder product-card-skeleton-circle"></span>
        @endif
    </div>
</div>
