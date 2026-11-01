@props([
    'averageRating' => 0,
    'totalReviews' => 0,
    'disabled' => true,
])

@php
    $rating = max(0, min(5, (float) $averageRating));
@endphp

<div class="d-inline-flex align-items-center gap-2">
    <div
        class="rating-container position-relative d-flex fg-secondary"
        aria-label="{{ __('products.rating', ['rating' => $rating]) }}"
    >

        <div class="d-flex fg-3 opacity-25">
            @for ($i = 0; $i < 5; $i++)
                <i class="bi bi-star-fill fs-md"></i>
            @endfor
        </div>

        <div class="d-flex fg-warning position-absolute top-0 start-0 overflow-hidden" style="width: {{ ($rating / 5) * 100 }}%;">
            @for ($i = 0; $i < 5; $i++)
                <i class="bi bi-star-fill fs-md"></i>
            @endfor
        </div>
    </div>

    <div class="small fg-3">
        <span class="fw-bold fg-body">{{ number_format($rating, 1) }}</span>
        <span>({{ $totalReviews }})</span>
    </div>

    @if (!$disabled)
    @endif
</div>
