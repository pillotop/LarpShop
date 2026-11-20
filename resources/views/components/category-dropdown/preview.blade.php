<p class="small fg-3 mb-2">{{ __('category_menu.explore') }}</p>
@include('components.category-dropdown.preview-images')
<h2 class="fs-lg fw-semibold mb-3" data-category-preview-name></h2>
<p class="small fg-3 mb-4" data-category-preview-description></p>
<div class="d-flex flex-column gap-2" data-category-preview-actions hidden>
    <a
        class="category-preview-action"
        data-category-preview-link
    >
        <span>{{ __('category_menu.browse') }}</span>
        <i class="bi bi-arrow-right" aria-hidden="true"></i>
    </a>
    <button
        type="button"
        class="category-preview-action"
        data-category-preview-select
    >
        <i class="bi bi-search" aria-hidden="true"></i>
        <span>{{ __('category_menu.search') }}</span>
    </button>
</div>
