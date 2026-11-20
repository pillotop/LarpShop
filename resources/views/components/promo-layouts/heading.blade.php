@props(['data', 'headingId', 'viewAllUrl' => null])

<header class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <h2 id="{{ $headingId }}" class="h3 fw-semibold mb-2">{{ $data->heading() }}</h2>
        @if ($data->section->introduction ?? $data->section->collection->description)
            <p class="fg-3 mb-0 promo-introduction">
                {{ $data->section->introduction ?? $data->section->collection->description }}
            </p>
        @endif
    </div>
    @if ($viewAllUrl)
        <a
            href="{{ $viewAllUrl }}"
            class="btn btn-sm btn-outline theme-secondary rounded-2"
        >{{ __('promotions.view_all') }}</a>
    @endif
</header>
