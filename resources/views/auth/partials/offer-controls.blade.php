<div class="auth-offer-controls d-flex align-items-center justify-content-between gap-3 mt-4">
    <div class="carousel-indicators" aria-label="{{ __('auth_showcase.choose_slide') }}">
        @foreach ($slides as $slide)
            <button
                type="button"
                @class(['active' => $loop->first])
                data-bs-target="#auth-offers-carousel"
                data-bs-slide-to="{{ $loop->index }}"
                aria-label="{{ __('auth_showcase.slides.' . $slide['key'] . '.title') }}"
                @if ($loop->first) aria-current="true" @endif
            ></button>
        @endforeach
    </div>

    <div class="d-flex align-items-center gap-2">
        <button
            type="button"
            class="auth-offer-control"
            data-bs-target="#auth-offers-carousel"
            data-bs-slide="prev"
            aria-label="{{ __('banners.previous') }}"
        >
            <span class="carousel-icon-prev" aria-hidden="true"></span>
        </button>
        <button
            type="button"
            class="auth-offer-control carousel-control-play-pause"
            data-bs-target="#auth-offers-carousel"
            data-bs-pause-label="{{ __('auth_showcase.pause') }}"
            data-bs-play-label="{{ __('auth_showcase.play') }}"
            aria-label="{{ __('auth_showcase.pause') }}"
        >
            <span class="carousel-icon-pause" aria-hidden="true"></span>
            <span class="carousel-icon-play" aria-hidden="true"></span>
        </button>
        <button
            type="button"
            class="auth-offer-control"
            data-bs-target="#auth-offers-carousel"
            data-bs-slide="next"
            aria-label="{{ __('banners.next') }}"
        >
            <span class="carousel-icon-next" aria-hidden="true"></span>
        </button>
    </div>
</div>
