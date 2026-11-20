<div
    id="{{ $contentId }}-preview"
    class="category-preview-images carousel mb-3"
    data-category-preview-images
    data-placeholder="{{ asset('images/product-placeholder.svg') }}"
    role="region"
    aria-label="{{ __('category_menu.images') }}"
>
    <div class="carousel-inner rounded-2" data-category-preview-slides></div>
    <div class="category-preview-controls d-flex justify-content-end gap-2 mt-2" data-category-preview-controls hidden>
        <button
            type="button"
            data-bs-target="#{{ $contentId }}-preview"
            class="btn btn-sm btn-outline-secondary rounded-2"
            data-bs-slide="prev"
            aria-label="{{ __('products.scroll_previous') }}"
        >&#8249;</button>
        <button
            type="button"
            data-bs-target="#{{ $contentId }}-preview"
            class="btn btn-sm btn-outline-secondary rounded-2 carousel-control-play-pause"
            data-bs-play-label="{{ __('category_menu.play') }}"
            data-bs-pause-label="{{ __('category_menu.pause') }}"
            aria-label="{{ __('category_menu.pause') }}"
        >
            <i class="bi bi-pause category-preview-pause" aria-hidden="true"></i>
            <i class="bi bi-play category-preview-play" aria-hidden="true"></i>
        </button>
        <button
            type="button"
            data-bs-target="#{{ $contentId }}-preview"
            class="btn btn-sm btn-outline-secondary rounded-2"
            data-bs-slide="next"
            aria-label="{{ __('products.scroll_next') }}"
        >&#8250;</button>
    </div>
</div>
