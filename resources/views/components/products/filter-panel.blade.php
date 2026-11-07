@props([
    'categories',
    'filters',
    'sortOptions',
    'keyword' => '',
    'hasActiveFilters' => false,
])

{{-- Product filter & sort form. Rendered inside <x-side-panel> via a <template>. --}}
<div class="drawer-header border-bottom">
    <h5 class="drawer-title fw-bold">{{ __('products.filters.title') }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="drawer" aria-label="{{ __('common.close') }}"></button>
</div>

<form method="GET" action="{{ route('products.index') }}" class="d-flex flex-column flex-grow-1 overflow-hidden">
    <div class="drawer-body">
        @if ($keyword !== '')
            <input type="hidden" name="q" value="{{ $keyword }}">
        @endif

        <fieldset class="mb-4">
            <legend class="h6 fw-semibold">{{ __('products.filters.sort_by') }}</legend>
            <div class="d-flex flex-column gap-2">
                @foreach (array_keys($sortOptions) as $sortKey)
                    <div class="d-flex align-items-center gap-2">
                        <input
                            id="filter-sort-{{ $sortKey }}"
                            type="radio"
                            name="sort"
                            value="{{ $sortKey }}"
                            class="radio"
                            @checked(($filters['sort'] ?? 'newest') === $sortKey)
                        >
                        <label for="filter-sort-{{ $sortKey }}" class="form-label">
                            {{ __('products.sort.' . $sortKey) }}
                        </label>
                    </div>
                @endforeach
            </div>
        </fieldset>

        <div class="mb-4">
            <label for="filter-category" class="form-label h6 fw-semibold">{{ __('products.filters.category') }}</label>
            <select id="filter-category" name="category" class="form-control">
                <option value="">{{ __('products.filters.all_categories') }}</option>
                @foreach ($categories as $categoryOption)
                    <option
                        value="{{ $categoryOption->slug }}"
                        @selected($filters['category'] === $categoryOption->slug || $filters['category'] === (string) $categoryOption->category_id)
                    >
                        {{ $categoryOption->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <fieldset class="mb-4">
            <legend class="h6 fw-semibold">{{ __('products.filters.price') }}</legend>
            <div class="row g-2">
                <div class="col-6">
                    <label for="filter-min-price" class="visually-hidden">{{ __('products.filters.min_price') }}</label>
                    <input
                        id="filter-min-price"
                        type="number"
                        name="min_price"
                        min="0"
                        step="0.01"
                        placeholder="{{ __('products.filters.min') }}"
                        value="{{ $filters['min_price'] ?? '' }}"
                        class="form-control"
                    >
                </div>
                <div class="col-6">
                    <label for="filter-max-price" class="visually-hidden">{{ __('products.filters.max_price') }}</label>
                    <input
                        id="filter-max-price"
                        type="number"
                        name="max_price"
                        min="0"
                        step="0.01"
                        placeholder="{{ __('products.filters.max') }}"
                        value="{{ $filters['max_price'] ?? '' }}"
                        class="form-control"
                    >
                </div>
            </div>
        </fieldset>

        <div class="d-flex align-items-center gap-2">
            <input
                id="filter-in-stock"
                type="checkbox"
                name="in_stock"
                value="1"
                class="check"
                @checked(!empty($filters['in_stock']))
            >
            <label for="filter-in-stock" class="form-label">{{ __('products.filters.in_stock') }}</label>
        </div>
    </div>

    <div class="border-top p-3 d-flex gap-2">
        <button type="submit" class="btn btn-solid theme-primary flex-grow-1">{{ __('products.filters.apply') }}</button>
        @if ($hasActiveFilters)
            <a
                href="{{ route('products.index', $keyword !== '' ? ['q' => $keyword] : []) }}"
                class="btn btn-outline theme-secondary"
            >
                {{ __('products.filters.reset') }}
            </a>
        @endif
    </div>
</form>
