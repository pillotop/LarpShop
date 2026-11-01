@props([
    'categories',
    'filters',
    'sortOptions',
    'keyword' => '',
    'hasActiveFilters' => false,
])

{{-- Product filter & sort form. Rendered inside <x-side-panel> via a <template>. --}}
<div class="drawer-header border-bottom">
    <h5 class="drawer-title fw-bold">Filter &amp; Sort</h5>
    <button type="button" class="btn-close" data-bs-dismiss="drawer" aria-label="Close"></button>
</div>

<form method="GET" action="{{ route('products.index') }}" class="d-flex flex-column flex-grow-1 overflow-hidden">
    <div class="drawer-body">
        @if ($keyword !== '')
            <input type="hidden" name="q" value="{{ $keyword }}">
        @endif

        <fieldset class="mb-4">
            <legend class="h6 fw-semibold">Sort By</legend>
            <div class="d-flex flex-column gap-2">
                @foreach ($sortOptions as $sortKey => $sortLabel)
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
                            {{ $sortLabel }}
                        </label>
                    </div>
                @endforeach
            </div>
        </fieldset>

        <div class="mb-4">
            <label for="filter-category" class="form-label h6 fw-semibold">Category</label>
            <select id="filter-category" name="category" class="form-control">
                <option value="">All Categories</option>
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
            <legend class="h6 fw-semibold">Price ($)</legend>
            <div class="row g-2">
                <div class="col-6">
                    <label for="filter-min-price" class="visually-hidden">Min Price</label>
                    <input
                        id="filter-min-price"
                        type="number"
                        name="min_price"
                        min="0"
                        step="0.01"
                        placeholder="Min"
                        value="{{ $filters['min_price'] ?? '' }}"
                        class="form-control"
                    >
                </div>
                <div class="col-6">
                    <label for="filter-max-price" class="visually-hidden">Max Price</label>
                    <input
                        id="filter-max-price"
                        type="number"
                        name="max_price"
                        min="0"
                        step="0.01"
                        placeholder="Max"
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
            <label for="filter-in-stock" class="form-label">In Stock Only</label>
        </div>
    </div>

    <div class="border-top p-3 d-flex gap-2">
        <button type="submit" class="btn btn-solid theme-primary flex-grow-1">Apply Filters</button>
        @if ($hasActiveFilters)
            <a
                href="{{ route('products.index', $keyword !== '' ? ['q' => $keyword] : []) }}"
                class="btn btn-outline theme-secondary"
            >
                Reset
            </a>
        @endif
    </div>
</form>
