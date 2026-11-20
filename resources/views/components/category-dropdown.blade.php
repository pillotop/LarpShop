@once
    @vite('resources/css/components/two_arrow_scroller.css')
@endonce

<input
    type="hidden"
    name="category"
    value="{{ $selectedCategory?->slug ?? '' }}"
    data-category-scope
    @disabled(! $selectedCategory)
>
<button
    type="button"
    class="search-category-btn d-flex gap-1 sm:gap-2 justify-content-between align-items-center rounded-start-pill border-0 px-2 sm:px-3 flex-shrink-0"
    aria-label="{{ __('common.select_category') }}"
    data-dropdown-trigger="categories"
    aria-expanded="false"
    data-dropdown-template-id="{{ $contentId }}-template"
    data-all-categories-label="{{ __('category_menu.all') }}"
>
    <span class="text-truncate" data-category-label>{{ $selectedCategory?->name ?? __('category_menu.all') }}</span>
    <svg
        class="dropdown-arrow icon-16 flex-shrink-0"
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 20 20"
        fill="none"
        stroke="currentColor"
        stroke-width="1.5"
        stroke-linecap="round"
        aria-hidden="true"
    >
        <path class="dropdown-arrow-path" d="M5.75 8.75 L10 13 L14.25 8.75"></path>
    </svg>
</button>
<template id="{{ $contentId }}-template" data-dropdown-template="categories">
    <div class="category-dropdown-menu shadow">
        @include('components.category-dropdown.menu')
    </div>
</template>
