<span
    class="category-menu-entry"
    data-category-entry
    data-category-scope="{{ $category->slug }}"
    data-category-images="{{ json_encode($previews[$category->getKey()] ?? []) }}"
    data-category-name="{{ $category->name }}"
    data-category-description="{{ $category->description ?: __('category_menu.description_fallback') }}"
>
    <a
        href="{{ route('categories.show', $category->slug) }}"
        @class(['category-menu-link', 'category-menu-parent' => $isParent ?? false])
        data-category-link
    >{{ $category->name }}</a>
    <button
        type="button"
        class="category-menu-scope"
        data-category-select
        aria-label="{{ __('category_menu.search_named', ['name' => $category->name]) }}"
        title="{{ __('category_menu.search_named', ['name' => $category->name]) }}"
    >
        <i class="bi bi-search" aria-hidden="true"></i>
    </button>
</span>
