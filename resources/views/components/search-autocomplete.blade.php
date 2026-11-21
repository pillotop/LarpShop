@php
    $suggestionsId = 'search-suggestions-' . \Illuminate\Support\Str::uuid();
@endphp

<div
    class="search-input-wrap d-flex align-items-center position-relative flex-grow-1"
    data-search-autocomplete
    data-suggestions-url="{{ route('products.search-suggestions') }}"
>
    <input
        type="search"
        name="q"
        value="{{ request()->query('q', '') }}"
        placeholder="{{ __('common.search_placeholder') }}"
        aria-label="{{ __('common.search_products') }}"
        maxlength="100"
        class="search-input position-relative border ps-2 sm:ps-3 pe-5 z-0 w-100 h-100"
        role="combobox"
        aria-autocomplete="list"
        aria-expanded="false"
        aria-controls="{{ $suggestionsId }}"
        autocomplete="off"
    >
    <button
        type="submit"
        class="search-submit-btn border-0 rounded-circle position-absolute z-1 end-0 me-1 sm:me-2 py-1 px-2"
        aria-label="{{ __('common.search_products') }}"
    >
        <svg class="icon-16" aria-hidden="true">
            <use href="/images/icons.svg#search-magnifier"></use>
        </svg>
    </button>
    <div class="search-suggestions border rounded-2 shadow" data-search-panel hidden>
        <x-loading-state class="p-3" data-search-loading />
        <p class="p-3 mb-0 fg-2" role="status" data-search-error hidden>
            {{ __('common.search_suggestions_error') }}
        </p>
        <p class="p-3 mb-0 fg-2" role="status" data-search-empty hidden>
            {{ __('common.search_suggestions_empty') }}
        </p>
        <div
            id="{{ $suggestionsId }}"
            role="listbox"
            aria-label="{{ __('common.search_suggestions') }}"
            data-search-results
        ></div>
    </div>
</div>
